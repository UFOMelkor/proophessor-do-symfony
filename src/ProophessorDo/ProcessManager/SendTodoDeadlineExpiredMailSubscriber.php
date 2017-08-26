<?php

namespace Prooph\ProophessorDo\ProcessManager;

use Prooph\ProophessorDo\Model\Todo\Event\TodoWasMarkedAsExpired;
use Prooph\ProophessorDo\Projection\Todo\TodoFinder;
use Prooph\ProophessorDo\Projection\User\UserFinder;
use Psr\Log\LoggerInterface;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;

/**
 * Class SendTodoDeadlineExpiredMailSubscriber
 *
 * @package Prooph\ProophessorDo\App\Mail
 * @author Michał Żukowski <michal@durooil.com
 */
final class SendTodoDeadlineExpiredMailSubscriber
{
    /**
     * @var UserFinder
     */
    private $userFinder;

    /**
     * @var TodoFinder
     */
    private $todoFinder;

    /**
     * @var TransportInterface
     */
    private $mailer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SendTodoDeadlineExpiredMailSubscriber constructor.
     * @param UserFinder $userFinder
     * @param TodoFinder $todoFinder
     * @param TransportInterface $mailer
     * @param LoggerInterface $logger
     */
    public function __construct(
        UserFinder $userFinder,
        TodoFinder $todoFinder,
        TransportInterface $mailer,
        LoggerInterface $logger
    )
    {
        $this->userFinder = $userFinder;
        $this->todoFinder = $todoFinder;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * @param TodoWasMarkedAsExpired $event
     * @return void
     */
    public function __invoke(TodoWasMarkedAsExpired $event)
    {
        $todo = $this->todoFinder->findById($event->todoId()->toString());
        $user = $this->userFinder->findById($todo->assignee_id);

        $message = sprintf(
            'Hi %s! Just a heads up: your todo `%s` has expired on %s.',
            $user->name,
            $todo->text,
            $todo->deadline
        );

        $mail = new Message();
        $mail->setBody($message);
        $mail->setEncoding('utf-8');
        $mail->setFrom('reminder@getprooph.org', 'Proophessor-do');
        $mail->addTo($user->email, $user->name);
        $mail->setSubject('Proophessor-do Todo expired');

        $this->mailer->send($mail);
        $this->logger->info('mail was sent to ' . $user->email);
    }
}
