<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\ChannelRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Mercure\Update;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChatController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/', name: 'app_home')]
    public function index(ChannelRepository $channelRepo): Response
    {   
        $allChannels = $channelRepo->findAll();

        return $this->render('chat/index.html.twig', [
            'allChannels' => $allChannels
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/channel/{id}', name: 'app_channel')]
    public function channel(Request $request, MessageRepository $messageRepo, ChannelRepository $channelRepo, Channel $channel): Response
    {
        $messagesForAChannel = $messageRepo->findBy(['channel' => $channel->getId()],['date' => 'ASC']);
        $channels = $channelRepo->findAll();
        $channelName = $channelRepo->findOneBy(['name' => $channel->getName()]);

        $form = $this->createForm(MessageType::class);
        $form->handleRequest($request);
    
        return $this->render('chat/channel.html.twig', [
            'messagesForAChannel' => $messagesForAChannel,
            'form' => $form->createView(),
            'channels' => $channels,
            'channelId' => $channel->getId(),
            'channelName' => $channelName
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('channel/{id}/post-message', name: 'app_channel_post_message', methods:['POST'])]
    public function channelPostMessage(Request $request, Channel $channel, EntityManagerInterface $em, HubInterface $hub): Response
    {
        $data = \json_decode($request->getContent(), true); // essayer toArray()
        if (empty($data['content'])) {
            throw new \Exception('No data sent');
        }

        $message = new Message();
        $message->setContent($data['content']);
        $message->setAuthor($this->getUser());
        $message->setDate(new \DateTime());
        $message->setChannel($channel);

        $em->persist($message);
        $em->flush();

        $update = new Update(
            'https://localhost:8000/channel/' . $channel->getId(),
            json_encode([
                'content' => $message->getContent(),
                'author' => $message->getAuthor(),
                'date' => $message->getDate()
            ])
        );

        $hub->publish($update);

        return new Response('Published');
    }
}
