<?php

declare(strict_types=1);

namespace Restfull\Mail;

use PHPMailer\PHPMailer\Exception;
use Restfull\Error\Exceptions;

/**
 *
 */
class PrepareService
{

    /**
     * @var Email
     */
    private $mail;

    /**
     * @var object
     */
    private $ORM;

    /**
     * @var string
     */
    private $table = 'services';

    /**
     * @param Email $mail
     * @param object $ORM
     * @param string $table
     * @return PrepareService
     */
    public function startORM(Email $mail, object $ORM, string $table = 'services'): PrepareService
    {
        if (is_object($ORM)) {
            $this->ORM = $ORM;
        }
        $this->mail = $mail;
        if ($this->table != $table) {
            $this->table = $table;
        }
        return $this;
    }

    /**
     * @param array $datas
     * @return PrepareService
     */
    public function writeData(array $datas): PrepareService
    {
        foreach (['to' => 'recipients', 'bcc' => 'recipientsBcc', 'cc' => 'recipientsCc'] as $partMethod => $key) {
            if (!isset($datas[$key])) {
                if ($this->mail->validerAddress($datas[$key])) {
                    $method = 'get' . ucfirst($partMethod) . 'Addresses';
                    $$key = $this->mail->{$method}();
                    if (is_array($$key)) {
                        $$key = count($$key) > 1 ? implode(';', $$key) : $$key;
                    }
                    $datas[$key] = $$key;
                }
            }
        }
        $options = ['fields' => array_keys($datas), 'conditions' => $datas];
        $this->ORM->typeExecuteQuery = 'create';
        $this->ORM->scannigTheMetadataOfTheseTables(
            ['main' => [['table' => $this->table]]],
            false
        )->datasInsertingDataToExecuteTheAssembledQuery(
            [[$options], ['table' => ['table' => $this->table]]],
            ['deleteLimit' => [false], 'returnResult' => true]
        );
        return $this;
    }

    /**
     * @param array $send
     * @return bool
     * @throws Exceptions
     * @throws Exception
     */
    public function readData(array $send): bool
    {
        $options['fields'] = [
            'id',
            'recipients',
            'if(recipientsBcc is null,"",recipientsBcc) as bcc',
            'if(recipientsCc is null,"",recipientsCc) as cc',
            'subject',
            'message'
        ];
        $options['conditions'] = ['status & ' => 'Ativo', 'read & ' => 'nao'];
        $this->ORM->typeExecuteQuery = 'all';
        foreach (
            $this->ORM->scannigTheMetadataOfTheseTables(
                ['main' => [['table' => $this->table]]],
                false
            )->datasInsertingDataToExecuteTheAssembledQuery(
                [[$options], ['table' => ['table' => $this->table]]],
                ['deleteLimit' => [false], 'returnResult' => true]
            )->excuteQuery(false, $options['fields']) as $result
        ) {
            $resultset['recipients'][] = $result->recipients;
            $resultset['bcc'][] = $result->bcc;
            $resultset['cc'][] = $result->cc;
            $resultset['subject'][] = $result->subject;
            $resultset['message'][] = $result->message;
        }
        $this->mail->addressing($send, $resultset['recipients']);
        foreach (['bcc', 'cc'] as $key) {
            if ($this->mail->validerAddress($resultset[$key])) {
                $method = $key === 'bcc' ? 'hiddenCopy' : 'copy';
                $this->{$method}($resultset[$key]);
            }
        }
        if ($this->mail->sends($resultset['subject'], $resultset['message'])) {
            $this->updateListOfEmailsSubmitted($resultset['ids']);
            return true;
        }
        return false;
    }

    /**
     * @param int $id
     * @param string $data
     * @return PrepareService
     */
    public function changeStatus(int $id, string $data): PrepareService
    {
        $options[0]['fields'] = [
            'recipients',
            'if(recipientsBcc is null,"",recipientsBcc) as recipientsBcc',
            'if(recipientsCc is null,"",recipientsCc) as recipientsCc',
            'status'
        ];
        $options[0]['conditions'] = ['status & ' => 'Ativo', 'id & ' => $id];
        $orm = $this->ORM->tableRegistory(['main' => [['table' => $this->table]]], ['datas' => $options]);
        $result = $orm->typeQuery('all')->queryAssembly()->executeQuery();
        $found = ['notFoundSearch', 'notFoundSearch'];
        foreach (['recipientsBcc', 'recipientsCc'] as $number => $key) {
            if ($this->mail->validerAddress($result[$key])) {
                if (stripos($result[$key], ';') !== false) {
                    $result[$key] = explode(';', $result[$key]);
                    unset($result[$key][array_keys($data, $result[$key])]);
                    $result[$key] = count($result[$key]) > 1 ? implode(';', $result[$key]) : (count(
                        $result[$key]
                    ) > 0 ? $result[$key][0] : '');
                    $found[$number] = 'foundSearch';
                }
            } else {
                unser($result[$key]);
            }
        }
        if (in_array('foundSearch', $found) === false) {
            if (stripos($result['recipients'], ';') !== false) {
                $result['recipients'] = explode(';', $result['recipients']);
                if (count($result['recipients']) > 0) {
                    unset($result['recipients'][array_keys($data, $result['recipients'])]);
                    $result['recipients'] = implode(';', $result['recipients']);
                    unset($result['status']);
                } else {
                    $result['status'] = 'desativado';
                }
            }
        } else {
            unset($result['recipients']);
        }
        $options[0]['fields'] = $result;
        unset($options[0]['conditions']['status & ']);
        $orm->typeQuery('update')->queryAssembly()->executeQuery();
        return $this;
    }

}