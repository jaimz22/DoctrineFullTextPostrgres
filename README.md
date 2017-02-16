# DoctrineFullTextPostrgres

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4754c670-381a-46fe-a0d6-42b189f83ebd/big.png)](https://insight.sensiolabs.com/projects/4754c670-381a-46fe-a0d6-42b189f83ebd)

A simple to use set of database types, and annotations to use postgresql's full text search engine with doctrine

## Installation
 * Register Doctrine Annotation:
 
 ```php
 \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace("VertigoLabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\");
 ```
 * Register Doctrine Type:
 
 ```php
 Type::addType('tsvector',\VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVectorType::class);
 ```
 * Register Doctrine Event Subscriber
 
 ```php
 $this->em->getEventManager()->addEventSubscriber(new \VertigoLabs\DoctrineFullTextPostgres\Common\TsVectorSubscriber());
 ```
 
 * Register Doctrine Functions
 ```php
 $doctrineConfig->addCustomStringFunction('tsquery', \VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsQueryFunction::class);
 $doctrineConfig->addCustomStringFunction('tsrank', \VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankFunction::class);
 $doctrineConfig->addCustomStringFunction('tsrankcd', \VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankCDFunction::class);
 ```
 
## Symfony installation
 
 * Add to config
 
 ```yaml
 doctrine:
     dbal:
         types:
             tsvector:   VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector
        mapping_types:
             tsvector: tsvector
     orm:
         entity_managers:
             default:
                 dql:
                     string_functions:
                         tsquery: VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsQueryFunction
                         tsrank: VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankFunction
                         tsrankcd: VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankCDFunction

services:
         vertigolabs.doctrinefulltextpostgres.listener:
                  class: VertigoLabs\DoctrineFullTextPostgres\Common\TsVectorSubscriber
                  tags:
                      - { name: doctrine.event_subscriber, connection: default }                         
 ```
 
## Usage
 * Create your entity
 
 You do not have to create column annotations for your fields that will hold your full text search vectors (tsvector) the columns will be created automatically.
 A TsVector annotation only requires the ```fields``` parameter. There are optional ```weight``` and ```language``` parameters as well, however they are not used yet.
 You do not need to set data for your TsVector field, the data will come from the fields specified in the ```fields``` property automatically when the object is flushed to the database
 
  ```php
  class Article
  {
      /**
       * @var string
       * @Column(name="title", type="string", nullable=false)
       */
      private $title;
  
      /**
       * @var TsVector
       * @TsVector(name="title_fts", fields={"title"})
       */
      private $titleFTS;
  	
      /**
       * @var string
       * @Column(name="body", type="text", nullable=true)
       */
      private $body;
  
       /**
       * @var TsVector
       * @TsVector(name="body_fts", fields={"body"})
       */
      private $bodyFTS;
  }
 ```
 
 * Insert some data
 
  You do not need to worry about setting any data to the fields marked with the TsVector annotation. The data for these fields will be automatically populated when you flush your changes to the database.
 
  ```php
  $article = new Article();
  $article->setTitle('Baboons Invade Seaworld');
  $article->setBody('In a crazy turn of events a pack a rabid red baboons invade Seaworld. Officials say that the Dolphins are being held hostage');
  $this->em->persist($article);
  $this->em->flush();
  ```
 
 * Query your database!
 
  When you query your database, you'll query against the actual data. the query will be modified to search using the fields marked with the TsVector annotation automatically
  
  ```php
  $query = $this->em->createQuery('SELECT a FROM Article a WHERE tsquery(a.title,:searchQuery) = true');
  $query->setParameter('searchQuery','Baboons');
  $result = $query->getArrayResult();
  ``` 
  
  If you'd like to retrieve the ranking of your full text search, simply use the tsrank function:
    
  ```php
  $query = $this->em->createQuery('SELECT a, tsrank(a.title,:searchQuery) as rank FROM Article a WHERE tsquery(a.title,:searchQuery) = true');
  $query->setParameter('searchQuery','Baboons');
  $result = $query->getArrayResult();
  
  var_dump($result[0]['rank']); // int 0.67907
  ``` 
  
  You can even order by rank:
    
  ```php
  $query = $this->em->createQuery('SELECT a FROM Article a WHERE tsquery(a.title,:searchQuery) = true ORDER BY tsrank(a.title,:searchQuery) DESC');
  ``` 
  
## TODO
 * Add language to SQL field definition
 * Add language and weighting to queries
