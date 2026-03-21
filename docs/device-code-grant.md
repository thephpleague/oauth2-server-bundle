# Device grant handling

The device code grant type is designed for devices without a browser or with limited input capabilities. In this flow, the user authenticates on another device—like a smartphone or computer—and receives a code to enter on the original device.

Initially, the device sends a request to `/device-code` with its client ID and scope. The server then returns a device code, a user code, and a verification URL. The user takes the code to a secondary device, opens the verification URL in a browser, and enters the user code.

Meanwhile, the original device continuously polls the `/token` endpoint with the device code. Once the user approves the request on the secondary device, the token endpoint returns the access token to the polling device.

## Requirements

You need to implement the verification URL yourself and handle the user code input : this bundle does not provide a route or UI for this.

## Security considerations

The OAuth 2.0 Device Grant explicitly prohibits the use of client secret credentials in the device flow.

Accordingly, this flow must only be used with public clients (use the `--public` option when creating the client via the CLI). The `/token` endpoint must be called with an empty `client_secret` parameter.

## Example

### Controller

This is a sample Symfony 7 controller to handle the user code input

```php
<?php

namespace App\Controller;

use League\Bundle\OAuth2ServerBundle\Manager\DeviceCodeManagerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

public function __construct(
    private readonly DeviceCodeRepository $deviceCodeRepository
) {
}

#[Route(path: '/verify-device', name: 'app_verify_device', methods: ['GET', 'POST'])]
public function verifyDevice(
    Request $request
): Response {
    $form = $this->createFormBuilder()
                 ->add('userCode', TextType::class, [
                     'required' => true,
                 ])
                 ->getForm()
                 ->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $deviceCode = $this->deviceCodeManager->findByUserCode($form->get('userCode')->getData());
            if (null === $deviceCode) {
                throw OAuthServerException::invalidGrant('Invalid user code');
            }
            $this->authorizationServer->completeDeviceAuthorizationRequest(
                $deviceCode->getIdentifier(),
                $this->getUser()->getId(),
                true // User has approved the device on his end
            );
            // Device code approved, show success message to user
        } catch (OAuthServerException $e) {
            // Handle exception (invalid code or missing user ID)
        }
    }

    // Render the form to the user
}
```

### Configuration

```yaml
league_oauth2_server:
    authorization_server:
        device_code_verification_uri: 'https://your-domain.com/verify-device'
```
