<?PHP
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

$user = UserService::getCurrentUser();

if (isset($user)) {
  echo sprintf('Welcome, %s(%s)! (<a href="%s">sign out</a>)',
               $user->getNickname(),
               $user->getEmail(),
               UserService::createLogoutUrl('/login_test'));
} else {
  echo sprintf('<a href="%s">Sign in or register</a>',
               UserService::createLoginUrl('/login_test'));
}
?>