Symfony-RDM – Import mapping from other files
===================================

## What it does

Using import-mapping you can import any mapping from another mapping-file.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
    <entity name="Foo\Bar\SomeEntity" table="some_entity">
        …
        <rdm:import field="lorem" path="foo.orm.xml" />
        <rdm:import field="ipsum" path="@SomeBundle/bar.orm.xml" />
        …
    </entity>
</doctrine-mapping>
```
