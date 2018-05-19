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

Example for what *foo.orm.xml* could look like:

```xml
<?xml version="1.0" encoding="utf-8"?>
<object
    xmlns="http://github.com/addiks/symfony_rdm/tree/master/Resources/mapping-schema.v1.xsd"
    xmlns:orm="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    class="Foo\Baz"
>
    <orm:field name="lorem" type="string" />
    <orm:field name="ipsum" type="string" />
    …
</object>
```
