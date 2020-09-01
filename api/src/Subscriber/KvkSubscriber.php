<?php

// src/Subscriber/AddresGetSubscriber.php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Adres;
use App\Service\KadasterService;
use App\Service\KvkService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class KvkSubscriber implements EventSubscriberInterface
{
    private $params;
    private $kvkService;
    private $serializer;

    public function __construct(ParameterBagInterface $params, KvkService $kvkService, SerializerInterface $serializer)
    {
        $this->params = $params;
        $this->kvkService = $kvkService;
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['kvk', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    public function kvk(RequestEvent $event)
    {
        $path = explode('/', parse_url($event->getRequest()->getUri())['path']);
        $route = $event->getRequest()->attributes->get('_route');
        $method = $event->getRequest()->getMethod();


        // Lats make sure that some one posts correctly
        if (Request::METHOD_GET !== $method || ($route != 'api_companies_get_collection' && $path[1] != 'companies')) {
            return;
        }
        $contentType = $event->getRequest()->headers->get('accept');
        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }
        switch ($contentType) {
            case 'application/json':
                $renderType = 'json';
                break;
            case 'application/ld+json':
                $renderType = 'jsonld';
                break;
            case 'application/hal+json':
                $renderType = 'jsonhal';
                break;
            default:
                $contentType = 'application/ld+json';
                $renderType = 'jsonld';
        }
        $branchNumber = null;
        if ($route != 'api_companies_get_collection' && $path[1] == 'companies' || $route == 'api_companies_get_collection' && $branchNumber = $event->getRequest()->query->get('branchNumber')) {
            if (!$branchNumber) {
                $branchNumber = $path[2];
            }
            $company = $this->kvkService->getCompanyOnBranchNumber($branchNumber);
            $response = $this->serializer->serialize(
                $company,
                $renderType,
                ['enable_max_depth'=> true, 'groups'=>'read']
            );

//            var_dump($company);
//            die;
            // Creating a response
            $response = new Response(
                $response,
                Response::HTTP_OK,
                ['content-type' => $contentType]
            );

//            $event->setResponse($response);
            $response->send();
        } else {

            $companies = $this->kvkService->getCompaniesOnSearchParameters($event->getRequest()->query->all());

            switch ($renderType) {
                case 'jsonld':
                    $response['@context'] = '/contexts/Company';
                    $response['@id'] = '/companies';
                    $response['@type'] = 'hydra:Collection';
                    $response['hydra:member'] = $companies;
                    $response['hydra:totalItems'] = count($companies);
                    break;
                default:
                    $response['companies'] = $companies;
                    $response['totalItems'] = count($companies);
                    $response['itemsPerPage'] = count($companies);
                    $response['_links'] = $response['_links'] = ['self' => ''];
                    break;
            }

            $response = $this->serializer->serialize(
                $response,
                $renderType,
                ['enable_max_depth'=> true, 'groups'=>'read']
            );

            // Creating a response
            $response = new Response(
                $response,
                Response::HTTP_OK,
                ['content-type' => $contentType]
            );
            $event->setResponse($response);
        }
    }
}
