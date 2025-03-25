# Password grant handling

The device code grant type is designed for devices without a browser or with limited input capabilities. In this flow, the user authenticates on another device—like a smartphone or computer—and receives a code to enter on the original device.

Initially, the device sends a request to /device-code with its client ID and scope. The server then returns a device code, a user code, and a verification URL. The user takes the code to a secondary device, opens the verification URL in a browser, and enters the user code.

Meanwhile, the original device continuously polls the /token endpoint with the device code. Once the user approves the request on the secondary device, the token endpoint returns the access token to the polling device.

## Requirements

You need to implement the verification URL yourself and handle the user code input : this bundle does not provide a route or UI for this.

## Example

### Controller

This is a sample Symfony 7 controller to handle the user code input

```php
<?php

namespace App\Controller;

use League\Bundle\OAuth2ServerBundle\Repository\DeviceCodeRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceCodeController extends AbstractController
{

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
                $this->deviceCodeRepository->approveDeviceCode($form->get('userCode')->getData(), $this->getUser()->getId());
                // Device code approved, show success message to user
            } catch (OAuthServerException $e) {
                // Handle exception (invalid code or missing user ID)
            }
        }

        return $this->render(
            'verify_device.html.twig',
            ['form' => $form]
        );
    }

}
```

### Configuration

```yaml
league_oauth2_server:
    authorization_server:
        device_code_verification_uri: 'https://your-domain.com/verify-device'
```
