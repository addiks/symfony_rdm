Symfony-RDM – Helper for using the Rich Domain Model in Symfony
===================================

## Why this bundle was implemented

This project was implemented because I think there are still some missing pieces for one to be able to **effectively**
perform domain driven design in software based on the symfony/doctrine2 framework(s) without the constant drive of
falling back to the anti-pattern of [anemic-domain-model][2] (please do read this link).

[2]: https://martinfowler.com/bliki/AnemicDomainModel.html

In domain driven design you are supposed to put your domain logic into their semantically designated domain objects. To
put it simply: Put customer related logic in the customer related domain objects. These domain objects may be entities,
value-objects, aggregates, services or repositories.

With symfony it will be easy for most of these types of objects to put their domain logic into them because for these
the symfony-DIC helps you in composing these objects with anything they need. The objects will be fully instanciated
again for every PHP-runtime with all needed dependencies.

With (doctrine2-) entities however you may find the problem that when loaded from database the entities will not be
instanciated as new objects but re-hydrated from an empty object-skeleton with only data that originates directly from
the database. Neither the constructor nor any wakeup process runs to fill the entity with data and objects that did not
originate from the database. Without any non-database objects the entitiy is per se "stranded" and cannot communicate
with anything that is not pure data. That means that it cannot trigger domain-related processes it would need to trigger
(like sending a happy-birthday-mail) because it cannot have any link to the infrastructure objects (mailers) that it
could send these signals accross to trigger these processes.

For example: Suppose you (and your domain-experts) decide that at the point of payment the invoice should send some kind
of PDF-receipt via e-mail to some customer. In a rich-domain-model (see link above) this e-mail should be triggered by
the "invoice" entity. For the invoice entity to be able to trigger an e-mail being sent it must at some point be able to
(directly or indirectly) call some outer infrastructure services outside of the business-logic that can send e-mails.

For an entity to be able to trigger a service it must have some reference to that service. Currently when you load an
entity from doctrine2 it will only be hydrated (loaded/filled) with data from the database but nothing else. As it
currently stands, doctrine2 is not able to hydrate an entity with non-entity objects (and it should not be able to,
that's IMHO the job of the application framework which in this case is symfony). You can inject a service via the
entity-constructor into some field of an entity and persist that entity to the database but as soon as you try to load
that entity later from database via the doctrine2 ORM you will find the field empty because doctrine2 does not know
about symfony services.

That is where this project comes into play. It provides a way (or several ways even) to define how to compose your
doctrine2 entities with other things that doctrine normally does not know about. Currently it can only "store" a symfony
service in a doctrine field by static mapping, this may be extended in the future. In this mapping you tell this bundle
which field in an entity should contain what service. Then it hooks into the doctrine2 events to hydrate that fields
with the needed services when you load your entities and even check's them when you try to persist new entities. That
way you are now able to express domain logic in your entities that normally would have to be in a separate service.

## Alternatives (and why i don't like them)

There are a couple other ways to implement domain logic that would work without assigning services to entities. However
i think that for domain-related software they are all inferior to the approach hinted at by this bundle. By "domain-
related software" i mean software that tries to emulate a part of reality or mental model of a domain-expert.

The following is a list of different approaches that are alternative to the approach hinted at by this bundle:

### No domain model at all

The most obvious alternative is to just not have an explicit model of the domain in the software at all. A domain-model
inside a software is/are in most cases a mental model represented by some seperated piece(s) of logic (classes, methods,
functions, ...) whose whole single purpose is to simulate the domain (e.g.: logistics, accounting, medicine, physics,
machinery, ...) as described by the domain-experts (e.g.: logisticians, accounters, doctors, physicists, mechanics,
...). One of the most important attributes of such a domain-model in software is the absence of any infrastructure logic
(file-handling, rendering, I/O, request-/response-handling, user-management, dependency-management, ...) in it.

Not having a distinct domain-model in domain-related software means that the domain logic is mingled with other non-
domain logic. In such a software a change to the domain means that you also have to change parts of the that is not
relevant to the domain and in reverse a change to the non-domain logic means that you also have to change domain logic.

### A non-rich or anemic domain model

### The command pattern
