<?php

namespace backend\services\auth;

use common\models\User;
use yii\base\InvalidArgumentException;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Request;

class AuthService
{
    public function isAuthByAuthorizationHeader(Request $request): bool
    {
        $authorization = $request->headers->get('authorization');

        if ($authorization && str_starts_with($authorization, 'Bearer ')) {
            $token = str_replace('Bearer ', '', $authorization);

            if (User::findIdentityByAccessToken($token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws BadRequestHttpException
     */
    public function getPostData(Request $request): array
    {
        if ($data = $request->post()) {
            return $data;
        }

        throw new BadRequestHttpException('Request body can not be empty');
    }

    /**
     * @throws Exception
     */
    public function verifyEmail(string $token): array
    {
        if (!$token) {
            throw new InvalidArgumentException('Verify email token cannot be blank.');
        }

        $user = User::findByVerificationToken($token);

        if (!$user) {
            $user = User::findOne(['verification_token' => $token]);

            if (!$user || $user->status === User::STATUS_DELETED) {
                throw new InvalidArgumentException('Wrong verify email token.');
            }

            return ['message' => 'Your email is already verified.'];
        }

        $user->status = User::STATUS_ACTIVE;
        $user->save(false);

        return ['message' => 'Your email has been verified'];
    }
}