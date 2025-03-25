<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Psr\Log\LoggerInterface;

class UsersAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        // Log des informations de connexion (sans le mot de passe pour la sécurité)
        $this->logger->info("Tentative de connexion pour l'email : {email}", ['email' => $email]);

        // Sauvegarde du dernier email utilisé
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
                if (!$user) {
                    throw new \Symfony\Component\Security\Core\Exception\UserNotFoundException("Utilisateur non trouvé.");
                }
                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();


        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->logger->info("Connexion réussie pour un utilisateur : {email}", ['email' => $user->getUserIdentifier()]);
            return new RedirectResponse($this->urlGenerator->generate('app_dash'));
        }
        else{
            $this->logger->info("Connexion réussie pour un utilisateur : {email}", ['email' => $user->getUserIdentifier()]);

        return new RedirectResponse($this->urlGenerator->generate('app_resource_index'));
        }        
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
