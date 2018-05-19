Symfony-RDM – List-Mapping
===================================

## What it does

The list mapping allows you to map a dynamicly-size array as a list of another reoccuring value onto the database.
Keys of the list-mapping may be string or integer and are preserved.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
    <entity name="Foo\Bar\SomeEntity" table="some_entity">

        <!-- Map's an array of ColorEnum objects to a db-column containing e.g.: "['red','green','orange']" -->
        <rdm:list field="colors" column="colors">
            <rdm:object class="Foo\ColorEnum" factory="self::get" serialize="$this->__toString" />
        </rdm:list>

        <!-- Map's a two-dim. array to a db-column containing e.g.: "[{'x':1.23,'y':4.56},{'x':7.89,'y':0.12}]" -->
        <rdm:list field="coordinates">
            <rdm:array>
                <field type="float" name="x" nullable="false" />
                <field type="float" name="y" nullable="false" />
            </rdm:array>
        </rdm:list>

    </entity>
</doctrine-mapping>
```
