<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Incident;
use AppBundle\Entity\IncidentType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
#use Doctrine\Bundle\DoctrineBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IncidentRepository extends EntityRepository
{
   # public function __construct(RegistryInterface $registry)
   # {
   #     parent::__construct($registry, Incident::class);
  #  }
    public $em ;

  public function __construct(EntityManagerInterface $entityManager)
    {
       
        $this->em=$entityManager;
    }
    
    public function findAll()
    {
     
       $qb = $this->createQueryBuilder("i");
       $qb->select('i.incidentid','i.personid','i.eventid','i.itypeid','i.name_recorded', 'i.sdate', 'i.edate', 'i.locid','i.location','pl.surname','pl.forename' , 'it.label as typename');
       $qb->join(' AppBundle\Entity\Person', 'pl', 'WITH', 'pl.personid = i.personid');
       $qb->join(' AppBundle\Entity\IncidentType', 'it', 'WITH', 'it.itypeid = i.itypeid');
       $qb->orderBy('pl.surname', 'ASC');
        $qbq = $qb->getQuery();
        $incidents =  $qbq->getResult();
       foreach( $incidents as $key=>$incident)
       {
          $url = "/incidents/".$incident['incidentid'];
          $incidents[$key]['link'] = $url;
          $incidents[$key]['label'] = $incident['surname'].", ".$incident['forename'];
          #echo( $incident['surname'].", ".$incident['forename']);
       }
       return $incidents;
       
    }
    
    public function seekByPerson($personid)
    {
      $sql = "select i.incidentid,i.personid,i.eventid,i.itypeid,i.name_recorded, i.sdate, i.edate, i.locid,i.location,it.label from AppBundle:incident i ";
      $sql .= " join 'AppBundle\Entity\IncidentType' it with it.itypeid = i.itypeid ";
      $sql .= " where i.personid = ".$personid." ";
      $sql .= " order by i.sdate ASC  ";
      $query = $this->em->createQuery($sql);
        $incidents = $query->getResult();
 
        $incid_ar = array();
       foreach( $incidents as $incident)
       {
          $url = "/incidents/".$incident['incidentid'];
          $incident['link'] = $url;
          $incid_ar[] = $incident;
       }
       return $incid_ar;
       
    }
    
     public function findbyParticipation($eventid,$personid)
    {
        $qb = $this->createQueryBuilder("i");
        $qb->select('i.incidentid','i.personid','i.eventid','i.itypeid','i.name_recorded', 'i.sdate', 'i.edate', 'i.locid','i.location','it.label' ,'i.sequence');
        $qb->join(' AppBundle\Entity\IncidentType', 'it', 'WITH', 'it.itypeid = i.itypeid');
        $qb->andWhere('i.personid = :pid');
        $qb->setParameter('pid', $personid);
        $qb->andWhere('i.eventid = :eid');
        $qb->setParameter('eid', $eventid);
        $qb->orderBy('i.sdate', 'ASC');
        $qbq = $qb->getQuery();
        $incidents =  $qbq->getResult();
        $incid_ar = array();
       foreach( $incidents as $incident)
       {
          $url = "/admin/incidents/".$incident['incidentid'];
          $incident['link'] = $url;
          $incid_ar[] = $incident;
       }
       return $incid_ar;
    }
    
    
    public function findOne($incidentid)
    {
      
         $sql = "select i.incidentid,i.personid,i.eventid,i.itypeid,i.name_recorded, i.rank, i.role, i.sdate, i.edate, i.locid,i.location,it.label,i.sequence from AppBundle:incident i ";
      $sql .= " join 'AppBundle\Entity\IncidentType' it with it.itypeid = i.itypeid ";
      $sql .= " where i.incidentid = ".$incidentid." ";
      $query = $this->em->createQuery($sql);
        $incidents = $query->getResult();
 
     #   $incident=  $qbq->getOneOrNullResult();
       return $incidents[0];
    }

    public function findLocations($locid)
    {
      $sql = "select i from AppBundle:incident i ";
      $sql .= " where i.locid = ".$locid." ";
      $sql .= " order by i.sdate ASC  ";
      $query = $this->em->createQuery($sql);
       $incidents = $query->getResult();

       
       return $incidents;
    }
    
}