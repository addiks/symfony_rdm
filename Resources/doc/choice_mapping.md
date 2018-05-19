Symfony-RDM – Choice-Mapping
===================================

## What it does

The choice mapping allows you to dynamicly choose between a pre-defined set of possible mappings for a given value.
Which actual mapping is chosen for a given value is stored in a seperate column.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
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
     * @var ?TransmitterInterface
     * @Choice(column="transmitter_name", choices={
     *  "foo_transmitter" = @Service(id="some_bundle.transmitter.foo"),
     *  "bar_transmitter" = @Service(id="some_bundle.transmitter.bar"),
     * })
     */
    private $transmitter;

    public function __construct(
        string $text,
        TransmitterInterface $transmitter = null
    ) {
        $this->id = uuid();
        $this->transmitter = $transmitter;
    }

    public function transmit()
    {
        $this->transmitter->transmitText($this->text);
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
