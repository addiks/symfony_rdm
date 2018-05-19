Symfony-RDM – Service-Mapping
===================================

## What it does

The service mapping allows you to map symfony (or other) services into fields of doctrine entites.

## XML Mapping

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
		…
	</entity>
</doctrine-mapping>
```

### Annotation mapping

```php
<?php

use Addiks\RDMBundle\Mapping\Annotation\Service;

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

    public function __construct(
        string $text,
        Swift_Mailer $mailer,
        MyThingyFactory $thingyFactory = null
    ) {
        $this->id = uuid();
        $this->mailer = $mailer;
        $this->thingyFactory = $thingyFactory;
    }
}
```

### YAML mapping

(Warning: YAML mapping is not fully implemented and may soon be removed completely!)

```yaml
# Doctrine.Tests.ORM.Mapping.User.dcm.yml
Doctrine\Tests\ORM\Mapping\User:
  type: entity
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
