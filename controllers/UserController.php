<?php

namespace app\controllers;

use yii\rest\ActiveController;
use yii\web\Response;
use app\models\User;
use Yii;
use yii\filters\auth\QueryParamAuth;
use yii\web\UnauthorizedHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\ContentNegotiator;

class UserController extends ActiveController
{
    public $modelClass = User::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::class,
            'only' => [
                'create', 'update', 'delete', 'view'
            ],
            'tokenParam' => 'key'
        ];

        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'view' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
        ];
    }

    // Создание пользователя
    public function actionCreate()
    {
        $model = new User();
        $model->load(Yii::$app->request->post(), '');
        if ($model->save()) {
            return ['status' => 'success', 'user' => $model];
        }
        return ['status' => 'error', 'errors' => $model->errors];
    }

    // Обновление информации пользователя
    public function actionUpdate($id)
    {
        $model = User::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Пользователь не найден");
        }

        $model->load(Yii::$app->request->post(), '');

        if ($model->save()) {
            return ['status' => 'success', 'user' => $model];
        }
        return ['status' => 'error', 'errors' => $model->errors];
    }
    // Удаление пользователя
    public function actionDelete($id)
    {
        $model = User::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Пользователь не найден");
        }

        if ($model->delete()) {
            return ['status' => 'success', 'message' => 'Пользователь удален'];
        }

        return ['status' => 'error', 'message' => 'Ошибка при удалении'];
    }

    // Авторизация пользователя
    public function actionLogin()
    {
        $request = Yii::$app->request;
        $user = User::findOne(['email' => $request->post('email')]);
        if ($user && Yii::$app->security->validatePassword($request->post('password'), $user->password_hash)) {
            return ['status' => 200];
        }

        throw new UnauthorizedHttpException("Неверные данные для входа");
    }

    // Получить информацию о пользователе
    public function actionView($id)
    {
        return User::findOne($id);
    }
}