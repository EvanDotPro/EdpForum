<?php

namespace EdpForum\Controller;

use Zend\Mvc\Controller\ActionController,
    Zend\View\Model\ViewModel,
    Zend\Stdlib\ResponseDescription as Response;

class DiscussController extends ActionController
{
    protected $discussService;

    protected $tag;

    protected $thread;

    public function threadsAction()
    {
        $verifyTag = $this->verifyTag();
        if (-1 === $verifyTag) {
            $response = $this->getResponse();
            $response->setStatusCode(404);
            return $response;
        }

        $tag = $this->getTag();

        if (0 === $verifyTag) {
            return $this->redirect()->toRoute('edpforum', array(
                'tagslug' => $tag->getSlug(),
                'tagid'   => $tag->getTagId()
            ));
        } 

        $threads = $this->getDiscussService()->getLatestThreads(25, 0, $tag->getTagId());

        return new ViewModel(array(
            'tag'     => $tag,
            'threads' => $threads
        ));
    }

    protected function messagesAction()
    {
        $verifyTag    = $this->verifyTag();
        $verifyThread = $this->verifyThread();

        if (-1 === $verifyTag || -1 == $verifyThread) {
            return $response;
        }

        $tag    = $this->getTag();
        $thread = $this->getThread();

        if (0 === $verifyTag || 0 === $verifyThread) {
            return $this->redirect()->toRoute('edpforum/thread', array(
                'tagslug'    => $tag->getSlug(),
                'tagid'      => $tag->getTagId(),
                'threadslug' => $thread->getSlug(),
                'threadid'   => $thread->getThreadId(),
            ));
        } 

        $messages = $this->getDiscussService()->getMessagesByThread($thread);

        return new ViewModel(array(
            'tag'      => $tag,
            'thread'   => $thread,
            'messages' => $messages
        ));
    }

    public function verifyTag()
    {
        $tag = $this->getTag();
        
        if (!$tag) {
            return -1;
        } else if ($tag->getSlug() !== $this->getEvent()->getRouteMatch()->getParam('tagslug')) {
            // fix slug name if it's wrong, redirect to the proper one
            return 0;
        }
        return 1;
    }

    public function verifyThread()
    {
        $thread = $this->getThread();

        if (!$thread) {
            $response = $this->getResponse();
            $response->setStatusCode(404);
            return -1;
        } else if ($thread->getSlug() !== $this->getEvent()->getRouteMatch()->getParam('threadslug')) {
            // fix slug name if it's wrong, redirect to the proper one
            return 0;
        }
        return 1;
    }

    public function getTag()
    {
        if (null !== $this->tag) {
            return $this->tag;
        }
        $tagId = $this->getEvent()->getRouteMatch()->getParam('tagid');
        return $this->tag = $this->getDiscussService()->getTagById($tagId);
    }

    protected function getThread()
    {
        if (null !== $this->thread) {
            return $this->thread;
        }
        $threadId = $this->getEvent()->getRouteMatch()->getParam('threadid');
        return $this->thread = $this->getDiscussService()->getThreadById($threadId);
    }

    public function getDiscussService()
    {
        return $this->discussService;
    }

    public function setDiscussService($discussService)
    {
        $this->discussService = $discussService;
        return $this;
    }
}
