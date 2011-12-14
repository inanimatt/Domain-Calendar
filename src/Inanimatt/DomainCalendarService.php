<?php
namespace Inanimatt;

use Symfony\Component\Console\Output\OutputInterface;
use Inanimatt\DomainCalendarException;

class DomainCalendarService
{
  const ALL_DOMAINS       = true;
  const UNEXPIRED_DOMAINS = false;
  const ORDER_BY_DOMAIN   = 3;
  const ORDER_BY_EXPIRY   = 4;
  
  protected $db = null;
  protected $whois = null;
  
  public function __construct($db, $whois)
  {
    $this->db = $db;
    $this->whois = $whois;
  }
  
  
  /**
   * Look up a domain's expiry and store it in the database
   */
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

  
  /**
   * Remove a domain record from the database
   */
  public function remove($domain)
  {
    $this->db->executeQuery('DELETE FROM domains WHERE domain_name = ?', array($domain));
  }
  
  
  
  /**
   * List stored domains
   */
  public function findAll($include_expired = self::ALL_DOMAINS, $order = self::ORDER_BY_DOMAIN)
  {
    
    switch($order)
    {
      case self::ORDER_BY_DOMAIN:
        $order = 'domain_name ASC';
        break;
      case self::ORDER_BY_EXPIRY:
        $order = 'expires ASC';
        break;
      default:
        throw new DomainCalendarException('Invalid "order" argument');
    }
    
    if ($include_expired)
    {
      return $this->db->fetchAll('SELECT domain_name, expires FROM domains ORDER BY '.$order);
    }
    else 
    {
      return $this->db->fetchAll('SELECT domain_name, expires FROM domains WHERE expires > ? ORDER BY '.$order, array(date_create()->format('Y-m-d')));
    }
  }


  
  /**
   * Refresh expiry info
   */
  public function refresh($force_all = false)
  {
    
    $results = $this->findAll();
    
    $output = array();
    
    foreach($results as $row)
    {
      $output_line = sprintf('%s: ', $row['domain_name']);
      
      if ($row['expires'] && (date_create($row['expires'])->format('U') > time()) && !$force_all)
      {
        $output[] = $output_line . sprintf('%s (cached, skipping)', $row['expires']);
        continue;
      }

      $result = $this->whois->Lookup($row['domain_name']);
      
      if (!is_array($result) || !isset($result['regrinfo']) || !isset($result['regrinfo']['domain']) || !isset($result['regrinfo']['domain']['expires']))
      {
        $output[] = $output_line . 'error requesting WHOIS record.';
        continue;
      }

      $expiry = new \DateTime($result['regrinfo']['domain']['expires']);
      
      $output[] = $output_line . $expiry->format('Y-m-d');

      // FIXME? Don't know whether to catch database exceptions and keep processing
      $this->db->executeQuery('UPDATE domains SET expires = ? WHERE domain_name = ?', array($expiry->format('Y-m-d'), $row['domain_name']));
      
    }
    
    return $output;
  }
  
  
  /**
   * Generate iCalendar
   */
  public function generateCalendar($remind_months = null, $remind_days = null, $remind_time = null)
  {
    $results = $this->findAll(self::UNEXPIRED_DOMAINS, self::ORDER_BY_EXPIRY);


    $calendar_template = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//inanimatt.com/DomainCalendar//NONSGML v1.0//EN
{events}
END:VCALENDAR
';

    $event_template = 'BEGIN:VEVENT
UID:{uid}
DTSTAMP:{dtstamp}
DTSTART:{start}
DTEND:{end}
SUMMARY:{summary}
BEGIN:VALARM
TRIGGER;VALUE=DATE-TIME:{reminder}
ACTION:DISPLAY
DESCRIPTION:{summary}
END:VALARM
END:VEVENT
';

    $events = array();
    foreach($results as $idx => $r)
    {
      $expiry = date_create($r['expires'], new \DateTimeZone('UTC'));

      $reminder = date_create_from_format('Y-m-d', $r['expires']);
      $reminder->setTimeZone(new \DateTimeZone('UTC'));

      if (!$reminder)
      {
        throw new DomainCalendarException(sprintf('Unable to parse expiry date for domain %s.', $r['domain_name']));
      }

      if ($remind_months)
      {
        $reminder->modify(sprintf('-%d months', $remind_months));
      }
      if ($remind_days)
      {
        $reminder->modify(sprintf('-%d days', $remind_days));
      }
      if ($remind_time)
      {
        if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $remind_time))
        {
          throw new DomainCalendarException('Invalid reminder time given');
        }

        $reminder->modify(sprintf('%s', $remind_time));
      }


      $event_data = array(
        '{uid}'       => sprintf('uid%d@%s', $idx, $r['domain_name']),
        '{dtstamp}'   => date_create()->format('Ymd\THis\Z'),
        '{start}'     => $expiry->format('Ymd\THis\Z'),
        '{end}'       => $expiry->format('Ymd\THis\Z'),
        '{summary}'   => sprintf('Domain %s expires on %s', $r['domain_name'], $expiry->format('l jS F')),
        '{reminder}'  => $reminder->format('Ymd\THis\Z'),
      );

      $events[] = strtr($event_template, $event_data);
    }

    $calendar = strtr($calendar_template, array('{events}' => join($events, PHP_EOL)));
    
    return $calendar;
  }


}