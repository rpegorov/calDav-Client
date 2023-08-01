<?php

namespace Services\CalDav;

use Doctrine\Common\Annotations\AnnotationReader;
use Services\CalDav\Objects\Calendar;
use Services\CalDav\Objects\CalendarsList;
use Services\CalDav\Objects\DeserializeInterface;
use Services\CalDav\Objects\EventResponse;
use Services\CalDav\Objects\SyncResponse;
use Services\ClassifierService;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CalDavFacade
{
    private $client;
    private $serializer;

    public function __construct()
    {
        $encoders = [new XmlEncoder()];
        $reflectionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();
        $propertyTypeExtractor = new PropertyInfoExtractor([$reflectionExtractor],
            [$phpDocExtractor, $reflectionExtractor],
            [$phpDocExtractor], [$reflectionExtractor],
            [$reflectionExtractor]);
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new ObjectNormalizer($classMetadataFactory, null, null, $propertyTypeExtractor), new ArrayDenormalizer(), new DateTimeNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function connect(string $path, string $user, string $pass)
    {

        $this->client = new CalDAVClient($path, $user, $pass);
        if (!$this->client->isValidCalDAVServer()) {

            if ($this->client->GetHttpResultCode() == '401') // unauthorisized
            {
            } elseif ($this->client->GetHttpResultCode() == '') // can't reach server
            {
                throw new CalDAVException('Can\'t reach server', $this->client);
            } else throw new CalDAVException('Could\'n find a CalDAV-collection under the url', $this->client);
        }
    }

    public function findCalendars(): DeserializeInterface
    {
        $xml = $this->client->FindCalendars(true);
        return $this->deserialize($xml, CalendarsList::class);
    }

    public function deserialize(string $xml, string $class):DeserializeInterface
    {
        $xml = str_replace('d:', '', $xml);

        try{
            $result =  $this->serializer->deserialize($xml, $class, 'xml');
        }catch(\Throwable $e){

            //throw new \Exception($e->getMessage());
            $result = new $class;
        }
        return $result;

    }

    public function createCalendar(string $name, string $displayName): bool
    {

        $this->client->CreateCalendar($name, $displayName);
        return $this->client->GetHttpResultCode() == 201 || $this->client->GetHttpResultCode() == 204;
    }

    /**
     * @throws \Exception
     */
    public function setCalendar(Calendar $calendar)
    {
        if (!isset($this->client)) throw new \Exception('No connection. Try connect().');
        $this->client->SetCalendar($this->client->first_url_part . $calendar->href);
    }

    /**
     * @throws \Exception
     */
    public function create(string $event): CalDAVObject
    {
        $this->checkCalendarSelected();
        $uid = $this->parseUuid($event);
        $this->client->GetEntryByHref($this->client->calendar_url . $uid . '.ics');
        if ($this->client->GetHttpResultCode() == '200') {
            throw new \Exception($this->url . $uid . '.ics already exists. UID not unique?');
        } else if ($this->client->GetHttpResultCode() == '404') ;
        else throw new \Exception('Recieved unknown HTTP status');

        $newEtag = $this->client->DoPUTRequest($this->client->calendar_url . $uid . '.ics', $event);
        if ($this->client->GetHttpResultCode() != '201') {
            if ($this->client->GetHttpResultCode() == '204') // $url.$uid.'.ics' already existed on server
            {
                throw new \Exception($this->client->calendar_url . $uid . '.ics already existed. Entry has been overwritten.');
            } else // Unknown status
            {
                throw new \Exception('Recieved unknown HTTP status'. "$this->client->GetHttpResultCode()");
            }
        }
        return new CalDAVObject($this->client->calendar_url.$uid.'.ics', $event, $newEtag);
    }

    public function delete(string $href, string $etag) {
        $this->checkCalendarSelected();
        $result = $this->client->GetEntryByHref($href);
        if(count($result) == 0) {
            throw new \Exception('Can\'t find '.$href.'on server');
        }
        if($result[0]['etag'] != $etag) {
            throw new \Exception('Wrong entity tag. The entity seems to have changed.');
        }

        // Do the deletion
        $this->client->DoDELETERequest($href, $etag);

        // Deletion successfull?
        if($this->client->GetHttpResultCode() != '200' and $this->client->GetHttpResultCode() != '204') {
            throw new \Exception('Recieved unknown HTTP status');
        }
    }

    /**
     * @throws \Exception
     */
    public function checkCalendarSelected()
    {
        if (!isset($this->client)) throw new \Exception('No connection. Try connect().');
        if (!isset($this->client->calendar_url)) throw new \Exception('No calendar selected. Try findCalendars() and setCalendar().');
    }

    protected function parseUuid(string $event)
    {
        if (!preg_match('#^UID:(.*?)\r?\n?$#m', $event, $matches)) {
            throw new \Exception('Can\'t find UID in $cal');
        } else {
            return $matches[1];
        }
    }

    /**
     * @throws \Exception
     */
    public function update(string $event): CalDAVObject
    {
        $this->checkCalendarSelected();
        $uid = $this->parseUuid($event);
        $result = $this->client->GetEntryByHref($this->client->calendar_url . $uid . '.ics');
        if ($this->client->GetHttpResultCode() == '200') ;
        else if ($this->client->GetHttpResultCode() == '404') throw new \Exception('Can\'t find ' . $this->client->calendar_url . $uid . '.ics' . ' on the server');
        else throw new \Exception('Recieved unknown HTTP status');
        $newEtag = $this->client->DoPUTRequest($this->client->calendar_url . $uid . '.ics', $event, $result[0]['etag']);
        if ( $this->client->GetHttpResultCode() != '204' && $this->client->GetHttpResultCode() != '200' )
        {
            throw new \Exception('Recieved unknown HTTP status', $this->client->GetHttpResultCode());
        }
        return new CalDAVObject($this->client->calendar_url.$uid.'.ics', $event, $newEtag);
    }

    public function getEvent(string $uuid): DeserializeInterface
    {
        $this->client->GetEntryByUid($uuid);
        $xml = $this->client->GetXmlResponse();
        return $this->deserialize($xml,EventResponse::class);

    }

    public function syncCalendar(string $token): DeserializeInterface
    {
        $xml = $this->client->SyncCalendar($token);
        return $this->deserialize($xml,SyncResponse::class);
    }

}