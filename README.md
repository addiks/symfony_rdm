Symfony-RDM – Helper for using the Rich Domain Model in Symfony

## What

This project introduces an easy and simple way to load services from the symfony-DIC into the fields of doctrine2
entities. This allows you to put much more of your business-logic into the entities which until now would have to be
inside a service and unreachable from within an entity (or would at least need some workarounds).

This was implemented with symfony 2.8 running on PHP 7.1 in mind because that is simply my use case. But i think it
should work in all current stable versions (at least up until symfony 3.x, probably even further), i have however not
tested this yet. It however does need PHP 7.1. When i get around to test more versions than symfony 2.8 i will update
this paragraph.

## How

It hooks into the “postLoad" event of doctrine and hydrates the marked fields with the described services. It also hooks
into the “prePersist" event and asserts that the marked fields actually contain their services. This is an additional
security layer to make sure you do not forget to inject these services (f.e.: in the entity-constructor). The assertion
can be disabled on a field-by-field basis using the property "lax=true". Only disable this check if you must and you
know what you are doing.

There are multiple ways of defining which services should be in what fields of the services:
Per annotations, YAML, XML, PHP or Static PHP.

I would suggest you to use the XML or YAML mapping because entities should be framework-agnostic. I personally prefer
XML over YAML because with XML you at least have a schemata while with yaml you often have to guess what keys are
allowed, what all the keys mean and who actually uses them. Below you find examples of XML, YAML and Annotation
configuration because those are the most used formats.

### Configuration via XML

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="http://github.com/addiks/symfony_rdm/tree/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
		<rdm:service field="mailer" id="swift_mailer" />
		<rdm:service field="thingyFactory" id="my.thingy.factory" lax="true" />
		…
	</entity>
</doctrine-mapping>
```

### Configuration via Yaml

```yaml
# Doctrine.Tests.ORM.Mapping.User.dcm.yml
Doctrine\Tests\ORM\Mapping\User:
  type: entity
  repositoryClass: Doctrine\Tests\ORM\Mapping\UserRepository
  table: cms_users
  fields:
    name:
      type: string
      length: 50
    email:
      type: string
      length: 32
  …
  services:
    mailer:
      id: swift_mailer
    thingyFactory:
      id: my.things.factory
      lax: true
```

### Configuration via annotations

```php
<?php

use Addiks\RDMBundle\Mapping\Service;

class MyEntity
{
    /**
     * @Service(id="swift_mailer")
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @Service(id="my.thingy.factory", lax="true")
     * @var ?MyThingyFactory
     */
    private $thingyFactory;

    public function __construct(Swift_Mailer $mailer, MyThingyFactory $thingyFactory = null)
    {
        $this->mailer = $mailer;
        $this->thingyFactory = $thingyFactory;
    }
}
```

## Setup

To enable this functionality first install the project via composer (symfony normally comes with composer) using the
following command: **composer require addiks/symfony_rdm**

Then [register the bundle in your symfony-application][1].
Prior to symfony-4.0 this is done in the file "app/AppKernel.php" inside the method "registerBundles". From 4.0 onwards
this is done in the file "config/bundles.php". (If you know how to automate this, please let me know.)

[1] http://symfony.com/doc/current/bundles.html

Symfony 2.x & 3.x:

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
        # ...
        new Addiks\RDMBundle\AddiksRDMBundle(), # <== Add this line
        new AppBundle\AppBundle(),
    );

    …

    return $bundles;
}
```

Symfony >= 4.0:

```php
// config/bundles.php
return [
    // 'all' means that the bundle is enabled for any Symfony environment
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    …
    Addiks\RDMBundle\AddiksRDMBundle::class => ['all' => true], # <== Add this line

];
```

After that the service-type should work.

## The future

This project may be extended with more features in the future, here are some ideas i have:

- Automatic initialization of value-objects in entities.
- Use aggregates (composed objects) in entities. (embeddables are just not enough)
- Allow object-decoration in, on and between entities.
- Allow dynamic service mapping, storing in the database which service was set on the member.

## Why

This project was implemented because I think there are still some missing pieces for one to be able to **effectively**
perform domain driven design in software based on the symfony/doctrine2 framework(s) without the constant drive of
falling back to the anti-pattern of [anemic-domain-model][2] (please do read this link).

[2] https://martinfowler.com/bliki/AnemicDomainModel.html

In DDD you are supposed to put your domain logic into their corresponding designated domain objects responsible for that
logic. These domain objects may be services, entities, value-objects, aggregates and sometimes repositories or other
types of objects. For most of these types of objects you will have no problem of putting the logic into them because for
these the symfony-DIC helps you in composing these objects. With (doctrine2-) entities however you may find the problem
that you simply cannot put logic inside them that can trigger some infrastructure mechanic outside of the domain logic
(without some kind of workaround) because there is no way of composing entity-objects with other non-entity-objects.

For example: If you (and your domain-experts) decide that at some point the invoice should send some kind of receipt via
e-mail to some customer, this e-mail should be triggered by the "invoice" entity. For the invoice entity to be able to
trigger an e-mail being sent it must at some point be able to (directly or indirectly) call some outer services outside
of the business-logic that can send e-mails.
For more information on this concept search for the term "Hexagonal architecture".

For an entity to be able to trigger a service it must have some reference to that service. Currently when you load an
entity from doctrine2 it will only be hydrated (loaded/filled) with data from the database but nothing else. As it
currently stands, doctrine2 is not able to hydrate an entity with services (and it should not be able to, that's IMHO
the job of the application framework which in this case is symfony). You can inject a service via the entity-constructor
into some field of an entity and persist that entity to the database but as soon as you try to load that entity later
from database via the doctrine2 ORM you will find the field empty because doctrine2 does not know about symfony
services.

That is where this project comes into play. This project provides a way (or several ways even) to define how to compose
your doctrine2 entities with other things that doctrine normally does not know about. Currently it can only "store" a
symfony service in a doctrine field by static mapping, this may be extended in the future. In this mapping you tell the
system which field in an entity should contain what service. Then it hooks into the doctrine2 events to hydrate that
fields with the needed services when you load your entities and even check's them when you try to persist new entities.
That way you are now able to express domain logic in your entities that normally would have to be in a separate service.
