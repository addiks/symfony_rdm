Symfony-RDM – Helper for using the Rich Domain Model in Symfony
===================================

[![Build Status](https://travis-ci.org/addiks/symfony_rdm.svg?branch=master)](https://travis-ci.org/addiks/symfony_rdm)
[![Build Status](https://scrutinizer-ci.com/g/addiks/symfony_rdm/badges/build.png?b=master)](https://scrutinizer-ci.com/g/addiks/symfony_rdm/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/addiks/symfony_rdm/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/addiks/symfony_rdm/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/addiks/symfony_rdm/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/addiks/symfony_rdm/?branch=master)

## What
The goal of this project is to enrich the doctrine2-ORM mapping capabilities so that entities do not have to be
developed *for* doctrine anymore, but make it possible to map **any** object from anywhere to a database using doctrine,
even if it was not developed for that purpose. It add's a lot of new ways on how data can be mapped from the database to
an object (and back).

The currently implemented features are the following:
 - [Load (symfony-) services into entity fields.](Resources/doc/service_mapping.md)
 - [Have a switch (choice) of which mapping to load into a field.](Resources/doc/choice_mapping.md)
 - [Load an array with static keys into a field.](Resources/doc/array_mapping.md)
 - [Load a list with dynamic length containing other mappings.](Resources/doc/list_mapping.md)
 - [Load any object with it's own inner mappings into a field.](Resources/doc/object_mapping.md) (You can map the same class in different ways.)
 - [Have any mapping nullable.](Resources/doc/nullable_mapping.md)
 - [Have a mapping always map to *NULL*.](Resources/doc/null_mapping.md)
 - [Import mapping from another file.](Resources/doc/import_mapping.md)

Each of these can be combined with any other, allowing for extremely dynamic ORM mapping capabilities.

## How
It hooks into the events of doctrine and hydrates the marked fields with the described values.
There are multiple ways of defining which mappings should be in what fields of the services:
Per annotations, YAML, XML, PHP or Static PHP. YAML-Mapping is not fully implemented yet and may be removed soon.

I would suggest you to use the XML (or YAML) mapping because entities should be framework-agnostic. I personally prefer
XML over YAML because with XML you at least have a schemata while with yaml you often have to guess what keys are
allowed, what all the keys mean and who actually uses them. For more detauls see the linked documentations above.

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

## Two ways of retrieving data: safe or fast
Using this extension to extend the ORM mapping of doctrine entities can (depending on the ORM mapping configuration) introduce new database
columns that doctrine would normally not know about. When loading or storing the data for these additional columns, there are two ways to
interact with them:

### The safe but slow method
The safe but slow way to deal with these additional database columns is to load them one by one outside of doctrine, every time an entity
is hydrated and store (UPDATE / INSERT) all modified entities in the database when the doctrine entity-manager is flushed.
This is safer as the faster approach because everything happens outside of doctrine's scope and changes to doctrine or other doctrine-
plugins cannot interfere with the functionality of the loading and storing process. At the same time this approach is slower - often even
MUCH slower - then the faster approach because now we may need to execute one extra select statement for every hydrated entity.
**This can have a serious performance impact!**
Nevertheless, this is the default approach because while being slower, it is also the safer approach.

### The fast but instable way
The alternative of loading and storing all data from and to the database outside of doctrine via extra SELECT statements is to let doctrine
load and store these data for us in doctrine's own mechanisms. This way the performance is (nearly) the same as if these columns were
native doctrine columns, no extra SELECT, UPDATE or INSERT statements need to be executed. The problematic part of this solution is that
for this to work, we need to make doctrine aware of all these additional database columns so that doctrine can handle them for us
**without** having doctrine actually using these data during it's own hydration of the entities. These are database-columns but **not**
entity fields. If doctrine would try to map these columns to the entity fields on it's own, it would fail because they have no corresponding
entity-fields by doctrines own logic. To prevent this from happening, this approach hooks deep into doctrine's own reflection mechanisms
and fakes these entitiy-fields for doctrine. From doctrines point of view, these fields on the entities actually exist even if they are
fake. This construct of hooking deep within doctrine makes a lot of assumptions about the internals of doctrine.
**If any of these assumptions fail (because either doctrine changes them in a new version or another extension changes them) then this
extension could fail to work properly!**
Because of this instability /  uncertainty, this method is not default but opt-in. To use this method you must define a symfony service
parameter called `addiks_rdm.data_loader.stability` and set it to `fast-and-unstable`.

`app/config/config.yml`:
```
parameters:
    addiks_rdm.data_loader.stability: 'fast-and-unstable'
```

## Service-FormType
This bundle also provides an additional new form-type called "ServiceFormType" which should prove valuable in
conjunction with the service-hydration-abilities of this bundle. It allows to specify a list of service-id's as choices
that can be selected between in a form and the selected being set on the entity.

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

- Allow object-decoration (custom proxy-objects) in, on and between entities.
- Allow to use simple arrays instead of doctrine collection objects [or even custom collections.][4]
- Inject service-container-parameters into entities (similar to services).
- Re-use data from one column in multiple fields (maybe even across multiple entities).
- [Generare non-object values from generator-services (or other routines) to be hydrated into unmapped fields][5]
- [Populate fields with aggregated data from the database.][6]
- Allow custom (non-generated) proxy-classes for final entities.

[4]: https://stackoverflow.com/questions/3691943
[5]: https://stackoverflow.com/questions/35414300
[6]: https://stackoverflow.com/questions/26968809
