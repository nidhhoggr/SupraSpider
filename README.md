# SupraSpider
###Author: Joseph Persie

A group of classes to provide extraction between a spider and its expected implementation. 

## Installation using git clone

1. Implement concrete implmentation of all the inetrfaces.
2. extend SupraSpider and implement protected methods where necessary.
3. register dependencies (dbal, job and  dom crawler).

## Example

```php
$dentistSpider = new UKDentistSpider();

$dentistSpider->setDomParser(new simple_html_dom);

$dentistSpider->setDebugMode(TRUE);

$SDBAL = new SpiderDBAL;

$dentistSpider->setDBAL($SDBAL);

$entities = $SDBAL->getEntities();

$SpiderJob = new SpiderJob;

$SpiderJob->isBatch();

$dateRange = implode(' to ', $dentistSpider->getDateRange());

$doctrineJob = $SDBAL->getJobByDateRange($dateRange);

$SpiderJob->init($doctrineJob);

$dentistSpider->setJob($SpiderJob);

$dentistSpider->generateReportsForEntities($entities);

$SDBAL->saveJob($SpiderJob);
```
