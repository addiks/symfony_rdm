Symfony-RDM – Helper for using the Rich Domain Model in Symfony
===================================

[![Build Status](https://travis-ci.org/addiks/symfony_rdm.svg?branch=master)](https://travis-ci.org/addiks/symfony_rdm)

## What

The goal of this project is to enrich the doctrine2-ORM in ways that allow the doctrine-entities to fulfill all the
roles that classical entities should fulfill in a rich/fat domain model. This project will try to lift as many technical
limitations of the current doctrine2-ORM in object-design as reasonable. This is meant to allow you (the developer) to
put much more of your business-logic into the entities which until now would have to be on other objects and unreachable
from within an entity (or would at least need some workarounds).

Currently this project consists of only two features, but it will be extended in the near future:
 - Load services from the symfony-DIC into the fields of doctrine2 entities.
 - Choose which service to inject from a list of services by a value from the database.

This was implemented with symfony 2.8 running on PHP 7.1 in mind because that is simply my use case. But i think it
should work in all current stable versions (at least up until symfony 3.x, probably even further), i have however not
tested this yet. It however does need PHP 7.1. When i get around to test more versions than symfony 2.8 i will update
this paragraph.

## How

It hooks into the events of doctrine and hydrates the marked fields with the described services. It also asserts that
the marked fields actually contain their services. This is an additional security layer to make sure you do not forget
to inject these services (f.e.: in the entity-constructor). The assertion can be disabled on a field-by-field basis
using the property "lax=true". Only disable this check if you must and you know what you are doing.

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
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
		<rdm:service field="mailer" id="swift_mailer" />
		<rdm:service field="thingyFactory" id="my.thingy.factory" lax="true" />

        <!-- Creates a new column 'transmitter_name' that contains either:
                - "foo_transmitter": loads the service 'some_bundle.transmitter.foo' into the field $transmitter
                - "bar_transmitter": loads the service 'some_bundle.transmitter.bar' into the field $transmitter
                - null: keeps the field $transmitter empty.
        -->
		<rdm:choice field="transmitter" column="transmitter_name">
		    <rdm:option name="foo_transmitter">
		        <rdm:service id="some_bundle.transmitter.foo" />
		    </rdm:option>
		    <rdm:option name="bar_transmitter">
		        <rdm:service id="some_bundle.transmitter.bar" />
		    </rdm:option>
		</rdm:choice>
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
  choices:
    transmitter:
      column: transmitter_name
      choices:
        foo_transmitter:
          service:
            id: some_bundle.transmitter.foo
        bar_transmitter:
          service:
            id: some_bundle.transmitter.bar
```

### Configuration via annotations

```php
<?php

use Addiks\RDMBundle\Mapping\Annotation\Service;
use Addiks\RDMBundle\Mapping\Annotation\Choice;

/**
 * @Entity
 */
class MyEntity
{

    /**
     * @Id
     * @Column(type="string")
     */
    private $id;

    /**
     * @Column(type="text")
     */
    private $text;

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

    /**
     * @var ?TransmitterInterface
     * @Choice(column="transmitter_name", choices={
     *  "foo_transmitter" = @Service(id="some_bundle.transmitter.foo"),
     *  "bar_transmitter" = @Service(id="some_bundle.transmitter.bar"),
     * })
     */
    private $transmitter;

    public function __construct(
        string $text,
        Swift_Mailer $mailer,
        MyThingyFactory $thingyFactory = null,
        TransmitterInterface $transmitter = null
    ) {
        $this->id = uuid();
        $this->mailer = $mailer;
        $this->thingyFactory = $thingyFactory;
        $this->transmitter = $transmitter;
    }

    public function transmit()
    {
        $this->transmitter->transmitText($this->text);
    }
}
```

## Setup

To enable this functionality first install the project via composer (symfony normally comes with composer) using the
following command: **composer require addiks/symfony_rdm**

Then [register the bundle in your symfony-application][1].
Prior to symfony-4.0 this is done in the file "app/AppKernel.php" inside the method "registerBundles". From 4.0 onwards
this is done in the file "config/bundles.php". (If you know how to automate this, please let me know.)

[1]: http://symfony.com/doc/current/bundles.html

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

After that this bundle should work. If not create an issue here and provide as much details about the environment this
is being used in, i may be able to help.

## Service-FormType

This bundle also provides a additional new form-type called "ServiceFormType" which should prove valuable in conjunction
with the service-hydration-abilities of this bundle. It allows to specify a list of service-id's as choices that can be
selected between in a form and the selected being set on the entity.

```php
<?php

use Addiks\RDMBundle\Symfony\FormType\ServiceFormType;

class MyEntityFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add("someField", ServiceFormType::class, [
            'required' => false,
            'choices' => [
                'app.example_services.foo' => 'foo',
                'app.example_services.bar' => 'bar',
            ]
        ]);
    }
    …
}
```

## The future

This project may be extended with more features in the future, here are some ideas i have:

- [Automatic initialization of value-objects in entities.][3]
- Use aggregates (composed objects) in entities. (embeddables are just not enough)
- Allow object-decoration (custom proxy-objects) in, on and between entities.
- Allow to use simple arrays instead of doctrine collection objects [or even custom collections.][4]
- Inject service-container-parameters into entities (similar to services).
- Re-use data from one column in multiple fields (maybe even across multiple entities).
- [Generare non-object values from generator-services (or other routines) to be hydrated into unmapped fields][5]
- Populate arrays in the entity with values from columns, services, parameters or other stuff.
- [Populate fields with aggregated data from the database.][6]
- Allow custom (non-generated) proxy-classes for final entities.

[3]: https://stackoverflow.com/questions/8440879
[4]: https://stackoverflow.com/questions/3691943
[5]: https://stackoverflow.com/questions/35414300
[6]: https://stackoverflow.com/questions/26968809

The (probably unachievable) vision for this project is to free entity-design from all technical limitations of the
doctrine mapping and allow to map, persist & load all types of objects from any PHP-library out there.
*Especially* if they were *not* designed with (doctrine-) ORM in mind. **Viva la liberté!**
