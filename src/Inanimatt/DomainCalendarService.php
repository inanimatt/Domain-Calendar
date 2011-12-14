<?php
namespace Inanimatt;

use Symfony\Component\Console\Output\OutputInterface;
use Inanimatt\DomainCalendarException;

class DomainCalendarService
{
  protected $db = null;
  protected $whois = null;
  
  public function __construct($db, $whois)
  {
    $this->db = $db;
    $this->whois = $whois;
  }
  
  
  public function add($domain)
  {
    
    $result = $this->whois->Lookup($domain);
    
    if (!is_array($result) || !isset($result['regrinfo']) || !isset($result['regrinfo']['domain']) || !isset($result['regrinfo']['domain']['expires']))
    {
      throw new DomainCalendarException('Error requesting WHOIS record.');
    }

    $expiry = new \DateTime($result['regrinfo']['domain']['expires']);

    try {
      $this->db->executeQuery('INSERT INTO domains (domain_name, expires) VALUES (?, ?)', array($domain, $expiry->format('Y-m-d')));
    } 
    catch (\PDOException $e)
    {

      if ($e->getMessage() == 'SQLSTATE[23000]: Integrity constraint violation: 19 column domain_name is not unique')
      {
        throw new DomainCalendarException('Domain already exists in database');
      } 
      else 
      {
        throw $e;
      }
      
    }
    
    return true;
  }
  
  
}