<?php

declare(strict_types=1);

namespace Restfull\Controller\Component;

use Restfull\Authentication\TwoSteps;
use Restfull\Controller\BaseController;
use Restfull\Controller\Component;
use Restfull\Error\Exceptions;

/**
 *
 */
class TwoFactorComponente extends Component
{

    /**
     * @var TwoSteps
     */
    private $twoFactor;

    /**
     * @param BaseController $controller
     *
     * @throws Exceptions
     */
    public function __construct(BaseController $controller)
    {
        $instance = $controller->instance();
        if (!isset($controller->Auth)) {
            throw new Exceptions('The AuthComponent must be instantiated for TwoFactorComponent to work.', 501);
        }
        parent::__construct($controller);
        $this->twoFactor = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Autentication' . DS_REVERSE . 'TwoSteps',
            ['instance' => $instance]
        );
        return $this;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function generator(): string
    {
        return $this->twoFactor->qrcodeValid()->getQrcode();
    }

    /**
     * @param string $text
     * @param array $data
     *
     * @return string
     * @throws Exceptions
     */
    public function validQrCode(string $text, array $data): string
    {
        if (empty($data['table']) || empty($data['userCondition']) || empty($data['field'])) {
            throw new Exceptions(
                'Some of the data is blank, it could be the table or the user\'s condition or table field.', 404
            );
        }
        if (strlen($text) != 6) {
            return 'The two factor code cannot be different than 6 characters.';
        }
        $valid = $this->twoFactor->validateCode($text);
        if (!$valid) {
            return 'The two factor code entered does not match.';
        }
        return $this->recovery($data);
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws Exceptions
     */
    private function recovery(array $data)
    {
        $twoFactor = $this->checkAtivador($data['table'], $data['userCondition']);
        if (!$twoFactor) {
            $recovery = $this->twoFactor->generate();
            $table = ['table' => $data['table']];
            $options = ['fields' => [$data['field']], 'conditions' => $data['userCondition']];
            $this->controller->querys(
                'update',
                ['main' => $table],
                [
                    'fields' => ['recovery' => $recovery, 'secret' => $this->twoFactor->getSecret()],
                    'conditions' => [
                        $data['field'] . ' & ' => $this->controller->querys(
                            'first',
                            ['main' => $table],
                            $options,
                            ['repository' => false]
                        )->{$data['field']}
                    ]
                ],
                ['repository' => false]
            );
            return $recovery;
        }
        return '';
    }

    /**
     * @param string $table
     * @param string $user
     *
     * @return mixed
     * @throws Exceptions
     */
    private function checkAtivador(string $table, string $user)
    {
        $valid = false;
        foreach (
            $this->controller->querys(
                'all',
                ['table' => $table],
                ['query' => 'show columns from %s'],
                ['repository' => false]
            ) as $data
        ) {
            if ($data->columns === 'twofactor') {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            throw new Exceptions("This {$table} table not found twofactor.");
        }
        return $this->controller->querys(
            'first',
            ['table' => $table],
            ['fields' => ['twofactor'], 'conditions' => $user],
            ['reository' => false]
        )->twofactor;
    }

    /**
     * @param string $backup
     * @param array $data
     *
     * @return string
     * @throws Exceptions
     */
    public function backup(string $backup, array $data): string
    {
        if (empty($data['table']) || empty($data['userCondition']) || empty($data['field'])) {
            throw new Exceptions(
                'Some of the data is blank, it could be the table or the user\'s condition or table field.', 404
            );
        }
        $table = ['table' => $data['table']];
        $id = $this->controller->querys(
            'first',
            ['main' => $table],
            ['fields' => [$data['field']], 'conditions' => $data['userCondition']],
            ['repository' => false]
        )->{$data['field']};
        $recoveries = explode(
            '<br>',
            $this->controller->querys(
                'first',
                ['main' => $table],
                ['field' => ['recovery'], 'conditions' => [$data['field'] . ' & ' => $id]],
                ['repository' => false]
            )->recovery
        );
        $valid = false;
        foreach ($recoveries as $recovery) {
            if ($backup === $recovery) {
                $valid = true;
            }
        }
        if ($valid) {
            $this->twoFactor->setSecret(
                $this->controller->querys(
                    'first',
                    ['main' => $table],
                    ['fields' => ['secret'], 'conditions' => [$data['field'] . ' & ' => $id]],
                    ['repository' => false]
                )->secret
            );
            return $this->twoFactor->qrcodeValid()->getQrcode();
        }
        return $valid;
    }
}