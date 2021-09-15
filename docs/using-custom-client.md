# Using custom client

1. Create a class that extends the `\League\Bundle\OAuth2ServerBundle\Model\AbstractClient` class.

    Example:

    ```php
    <?php

    declare(strict_types=1);

    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;

    #[ORM\Entity]
    class Client extends AbstractClient
    {
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        protected $identifier;

        #[ORM\Column(type: 'string')]
        private $image;

        // other properties, getters, setters, ...
    }
    ```

2. In order to use the new client instead of `League\Bundle\OAuth2ServerBundle\Model\Client`, edit the configuration like the following:

    ```yaml
    league_oauth2_server:
        client:
            classname: App\Entity\Client
    ```
