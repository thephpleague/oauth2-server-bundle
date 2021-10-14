# Contributing

All contributions are **welcome** and **very much appreciated**.

We accept contributions via Pull Requests on [Github](https://github.com/thephpleague/oauth2-server-bundle).

## Pull Request guidelines

- **Add tests!** - We strongly encourage adding tests as well since the PR might not be accepted without them.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Code linting

This bundle enforces the PSR-2 and Symfony code standards during development by using the [PHP CS Fixer](https://cs.sensiolabs.org/) utility. Before committing any code, you can run the utility to fix any potential rule violations:

```sh
vendor/bin/php-cs-fixer fix
```

## Testing

You can run the whole test suite using the following command:

```sh
vendor/bin/simple-phpunit
```

**Happy coding**!
