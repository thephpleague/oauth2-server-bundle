# Longer client identifiers

The identifiers the bundle generates are 32 characters long, and so is the column of the default client class. An application that gives its clients other identifiers, for example UUIDs with a prefix or URLs, can make the column 255 characters long:

```yaml
league_oauth2_server:
    client:
        allow_long_identifier: true
```

The identifier is the primary key of the client table and the token tables reference it, so 255 keeps the key within the index size of MySQL.

Changing the option changes the schema. Generate and run a migration:

```console
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Not setting the option to `true` is deprecated. The column will be 255 characters long in 3.0, and the migration is mandatory then.

The option applies to the default client class. A [custom client class](using-custom-client.md) declares its identifier column itself, so change `length` there.
