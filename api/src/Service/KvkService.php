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

use App\Entity\Adres;
//use Doctrine\ORM\EntityManager;
use App\Entity\Address;
use App\Entity\Company;
use App\Entity\TradeName;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    public function __construct(ParameterBagInterface $params, CacheInterface $cache, EntityManagerInterface $manager)
    {
        $this->params = $params;
        $this->cache = $cache;
        $this->manager = $manager;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->params->get('common_ground.components')['kvk']['location'],
            // You can set any number of default request options.
            'timeout'  => 4000.0,
            // This api key needs to go into params
            'headers' => ['X-Api-Key' => $this->params->get('common_ground.components')['kvk']['apikey']],
        ]);
    }

    public function getCompany(string $branchNumber){

        $item = $this->cache->getItem('company_'.md5($branchNumber));
        if($item->isHit()){
            return $item->get();
        }
        $query = ['branchNumber'=>$branchNumber, 'branch'=>'false', 'mainBranch'=>'true'];
        $response = $this->client->get('companies', ['query'=>$query])->getBody();
//        var_dump($response);

        $response = json_decode($response, true);
        $response = $response['data']['items'][0];

        $item->set($response);
        $item->expiresAt(new DateTime('tomorrow 4:59'));
        $this->cache->save($item);

        return $item->get();
    }
    public function getObject($branch): Company
    {
//        var_dump($branch);
        $company = new Company();
        $company->setBranchNumber($branch['branchNumber']);
        $company->setKvkNumber($branch['kvkNumber']);
        $company->setRsin($branch['rsin']);

        $company->setHasEntryInBusinessRegister($branch['hasEntryInBusinessRegister']);
        $company->setHasNonMailingIndication($branch['hasNonMailingIndication']);

        $company->setIsLegalPerson($branch['isLegalPerson']);
        $company->setIsBranch($branch['isBranch']);
        $company->setIsMainBranch($branch['isMainBranch']);

        foreach($branch['addresses'] as $rawAddress){
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
        if(key_exists('tradeNames', $branch)){
            $company->setTradeNames($branch['tradeNames']);
        }

        // Let see what we got here in terms of object

        $this->manager->persist($company);
        $company->setId($branch['branchNumber']);
        $this->manager->persist($company);

        return $company;
    }

    public function getCompaniesOnSearchParameters($huisnummer, $postcode)
    {
        // Lets start with th getting of nummer aanduidingen
        $now = new \Datetime();
        $query = ['huisnummer'=>$huisnummer, 'postcode'=>$postcode, 'geldigOp'=>$now->format('Y-m-d')];
        $nummeraanduidingen = $this->getNummeraanduidingen($query);

        // Lets setup an responce
        $responces = [];
        // Then we need to enrich that
        foreach ($nummeraanduidingen['nummeraanduidingen'] as $nummeraanduiding) {
            $responces[] = $this->getObject($nummeraanduiding);
        }

        return $responces;
    }

    public function getCompanyOnBranchNumber($branchNumber) : Company
    {

        $company = $this->getCompany($branchNumber);

        return $this->getObject($company);
    }
}
