<?php

// Conduction/CommonGroundBundle/Service/KadasterService.php

/*
 * This file is part of the Conduction Common Ground Bundle
 *
 * (c) Conduction <info@conduction.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

//use Doctrine\ORM\EntityManager;
use App\Entity\Address;
use App\Entity\Company;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KvkService
{
    private $config;
    private $params;
    private $client;
    private $commonGroundService;
    private $manager;
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(ParameterBagInterface $params, CacheInterface $cache, EntityManagerInterface $manager, CommonGroundService $commonGroundService)
    {
        $this->params = $params;
        $this->cache = $cache;
        $this->manager = $manager;
        $this->commonGroundService = $commonGroundService;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->params->get('common_ground.components')['kvk']['location'],
            // You can set any number of default request options.
            'timeout'  => 4000.0,
            // This api key needs to go into params
            'headers' => [],
            // Do not check certificates
            'verify' => false,
        ]);
    }

    private function convertQuery($query): string
    {
        if (is_array($query) && $query != []) {
            $queryString = '';
            $iterator = 0;
            foreach ($query as $parameter => $value) {
                $queryString .= "$parameter=$value";

                $iterator++;
                if ($iterator < count($query)) {
                    $queryString .= '&';
                }
            }
            $query = $queryString;
        } elseif ($query == []) {
            $query = '';
        }

        return $query;
    }

    public function getCompany(string $branchNumber)
    {
        $item = $this->cache->getItem('company_'.md5($branchNumber));
        if ($item->isHit()) {
            return $item->get();
        }
        $query = ['branchNumber'=>$branchNumber, 'branch'=>'false', 'mainBranch'=>'true', 'user_key'=>$this->params->get('common_ground.components')['kvk']['apikey']];
        $response = $this->client->get('companies', ['query'=>$this->convertQuery($query)])->getBody();

        $response = json_decode($response, true);
        if (count($response['data']['items']) < 1) {
            $query = ['kvkNumber'=>$branchNumber, 'branch'=>'false', 'mainBranch'=>'true', 'user_key'=>$this->params->get('common_ground.components')['kvk']['apikey']];
            $response = $this->client->get('companies', ['query'=>$this->convertQuery($query)])->getBody();

            $response = json_decode($response, true);
            if (count($response['data']['items']) > 1) {
                foreach ($response['data']['items'] as $responseItem) {
                    if (!key_exists('branchNumber', $responseItem)) {
                        $response = $responseItem;
                    }
                }
            } elseif (count($response['data']['items']) == 1) {
                $response = $response['data']['items'][0];
            } else {
                throw new HttpException(404, 'not found');
            }
        } else {
            $response = $response['data']['items'][0];
        }

        $item->set($response);
        $item->expiresAt(new DateTime('+ 1 week'));
        $this->cache->save($item);

        return $item->get();
    }

    public function getCompanies(array $query)
    {
//        var_dump($query);
        $item = $this->cache->getItem('companies_'.md5(implode('', $query)));
        if ($item->isHit()) {
            return $item->get();
        }
        $query['user_key'] = $this->params->get('common_ground.components')['kvk']['apikey'];
        $response = $this->client->get('companies', ['query'=>$this->convertQuery($query)])->getBody();
//        var_dump($response);

        $response = json_decode($response, true);
        $response = $response['data']['items'];

        $item->set($response);
        $item->expiresAt(new DateTime('+ 1 week'));
        $this->cache->save($item);

        return $item->get();
    }

    public function getObject($branch): Company
    {
        $company = new Company();
        if (key_exists('branchNumber', $branch)) {
            $company->setBranchNumber($branch['branchNumber']);
        }
        $company->setKvkNumber($branch['kvkNumber']);
        if (key_exists('rsin', $branch)) {
            $company->setRsin($branch['rsin']);
        }

        $company->setHasEntryInBusinessRegister($branch['hasEntryInBusinessRegister']);
        $company->setHasNonMailingIndication($branch['hasNonMailingIndication']);

        $company->setIsLegalPerson($branch['isLegalPerson']);
        $company->setIsBranch($branch['isBranch']);
        $company->setIsMainBranch($branch['isMainBranch']);

        if (key_exists('addresses', $branch)) {
            foreach ($branch['addresses'] as $rawAddress) {
                $address = new Address();
                $address->setType($rawAddress['type']);
                $address->setStreet($rawAddress['street']);
                $address->setHouseNumber($rawAddress['houseNumber']);
                $address->setHouseNumberAddition($rawAddress['houseNumberAddition']);
                $address->setPostalCode($rawAddress['postalCode']);
                $address->setCity($rawAddress['city']);
                $address->setCountry($rawAddress['country']);

                $this->manager->persist($address);

                $address->setId(Uuid::uuid4());
                $this->manager->persist($address);

                $company->addAddress($address);
            }
        }
        if (key_exists('tradeNames', $branch)) {
            $company->setTradeNames($branch['tradeNames']);
            if (key_exists('businessName', $branch['tradeNames'])) {
                $company->setName($branch['tradeNames']['businessName']);
            } elseif (!is_array(array_values($branch['tradeNames'])[0])) {
                $company->setName(array_values($branch['tradeNames'])[0]);
            } else {
                $company->setName($branch['branchNumber']);
            }
        } else {
            $company->setName($branch['branchNumber']);
        }

        // Let see what we got here in terms of object

        $this->manager->persist($company);
        if (key_exists('branchNumber', $branch)) {
            $company->setId($branch['branchNumber']);
        } else {
            $company->setid($branch['kvkNumber']);
        }
        $this->manager->persist($company);

        return $company;
    }

    public function getCompaniesOnSearchParameters($query): array
    {
        // Lets start with th getting of nummer aanduidingen
        $companies = $this->getCompanies($query);

        // Lets setup an responce
        $results = [];
        // Then we need to enrich that
        foreach ($companies as $company) {
            $results[] = $this->getObject($company);
        }

        return $results;
    }

    public function getCompanyOnBranchNumber($branchNumber): Company
    {
        $company = $this->getCompany($branchNumber);

        return $this->getObject($company);
    }
}
