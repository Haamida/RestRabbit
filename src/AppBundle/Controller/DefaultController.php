<?php

namespace AppBundle\Controller;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $context=$this->createOrUseConnection($this->getParameter('topic_rb'),$this->getParameter('queue_rb'));
        $form = $this->createFormBuilder()
            ->add('Send Request', SubmitType::class, ['attr' => ['class'=>'btn draw-border']])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Randomly pick an endpoint to call
             * By default Get information about SpaceX
             */
            switch (rand(1,3)){
                case 1:    $message = $context->createMessage('roadster');
                break;
                case 2:  $message = $context->createMessage('rockets');
                break;
                case 3:  $message = $context->createMessage('missions');
                break;
                default:  $message = $context->createMessage('info');
            }

            $context->createProducer()->send($this->createOrUseQueue($context, $this->getParameter('queue_rb')), $message);
            return $this->render('default/index.html.twig',['form'=>$form->createView()]);
        }
        return $this->render('default/index.html.twig',['form'=>$form->createView()]);
    }
    /*
     * Create or use an existant Context and topic
     */
    public function createOrUseConnection($topicName,$queueName){
        $factory = new AmqpConnectionFactory([
            'host' => $this->getParameter('host_rb'),
            'port' => $this->getParameter('port_rb'),
            'vhost' => $this->getParameter('vhost_rb'),
            'user' => $this->getParameter('user_rb'),
            'pass' => $this->getParameter('pass_rb'),
            'persisted' => true,
        ]);


        $context = $factory->createContext();
        $topic = $context->createTopic($topicName);
        $topic->setType(AmqpTopic::TYPE_DIRECT);
        $context->declareTopic($topic);
        $queue=$this->createOrUseQueue($context,$queueName);
        $context->bind(new AmqpBind($topic, $queue));
        return $context;
    }
    /*
     * Create or use an existant Queue
     */
    public function createOrUseQueue($context,$queueName){
        $queue = $context->createQueue($queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $context->declareQueue($queue);
        return $queue;
    }
}
