<?php
namespace App\Service;

use App\Repository\UserRepository;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class TokenGenerator implements TokenGeneratorInterface
{
    private $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function generateToken(): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
        $user = $this->userRepository->findBy(array('token' => $token));
        if (!$user) {
            return $token;
        }
        return $this->generateToken();
        
    }


    public function cryptage($min, $max) {
        $range = $max - $min;
        if ($range < 0)
            return $min; // not so random...
            $log = log ( $range, 2 );
            $bytes = ( int ) ($log / 8) + 1; // length in bytes
            $bits = ( int ) $log + 1; // length in bits
            $filter = ( int ) (1 << $bits) - 1; // set all lower bits to 1
            do {
                $rnd = hexdec ( bin2hex ( openssl_random_pseudo_bytes ( $bytes ) ) );
                $rnd = $rnd & $filter; // discard irrelevant bits
            } while ( $rnd >= $range );
            return $min + $rnd;
    }

    public function getCode($length = 6, $codeType = 'string') {
        $token = "";
        $code = '';
        if($codeType == 'string')
        {
            $code = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $code .= "abcdefghijklmnopqrstuvwxyz";
        }
        if(in_array($codeType, ['string', 'int']))
        {
            $code .= "0123456789";
        }
        for($i = 0; $i < $length; $i ++) {
            $token .= $code [$this->cryptage ( 0, strlen ( $code ) )];
        }
        return $token;
    }
}