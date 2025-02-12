<?php

namespace app\models;

// use yii\base\BaseObject;
use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

class User extends ActiveRecord implements IdentityInterface
{
    public $password;
    public $authKey;

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function rules(): array
    {
        return [
            [['surname', 'first_name', 'email', 'password'], 'required'],
            [['surname', 'first_name', 'patronymic', 'email'], 'string', 'max' => 255],
            [['surname', 'first_name', 'patronymic', 'email'], 'filter', 'filter' => 'trim', 'skipOnEmpty' => true],
            [['surname'],
                'match',
                'pattern' => '/^[А-Яа-яЁё]+(?:[-\s][А-Яа-яЁё]+)*$/u',
                'message' => 'Фамилия может содержать только русские буквы, пробелы и дефисы.',
            ],
            [['first_name'],
                'match',
                'pattern' => '/^[А-Яа-яЁё\s]+(?:-[А-Яа-яЁё\s]+)*$/u',
                'message' => 'Имя может содержать только русские буквы, пробелы и дефисы.',
            ],
            [['patronymic'],
                'match',
                'pattern' => '/^[А-Яа-яЁё\s]+(?:-[А-Яа-яЁё\s]+)*$/u',
                'message' => 'Отчество может содержать только русские буквы, пробелы и дефисы.',
            ],
            // Пароль: длина и символы
            [['password'], 'string', 'min' => 8, 'max' => 32],
            [['password'],
                'match',
                'pattern' => '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).+$/',
                'message' => 'Пароль должен содержать хотя бы одну цифру, один спецсимвол и латинские буквы.'
            ],
            [['password'],
                'match',
                'pattern' => '/^[^а-яА-ЯёЁ]*$/u',
                'message' => 'Пароль не должен содержать кириллицу.'
            ],
            // Email
            ['email', 'email'],
            ['email', 'unique',
                'targetClass' => User::class,
                'targetAttribute' => 'email',
                'message' => 'Этот email уже зарегистрирован.'
            ],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => function () {
                    return time();
                },
            ],
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->auth_key = Yii::$app->security->generateRandomString();
        }

        if (!empty($this->password)) {
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        }
    
        return parent::beforeSave($insert);
    }

    public function fields(): array
    {
        return ['id', 'surname', 'first_name', 'patronymic', 'email'];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }
}