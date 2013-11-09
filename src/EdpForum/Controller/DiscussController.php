<?php

namespace EdpForum\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ResponseDescription as Response;

class DiscussController extends AbstractActionController
{
    protected $discussService;

    protected $tag;

    protected $thread;

    public function forumsAction()
    {
        $tags = $this->getDiscussService()->getTags();

        return new ViewModel(array(
            'tags' => $tags
        ));
        
    }
    
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
        $form = $this->getServiceLocator()->get('edpdiscuss_form');
        
        return new ViewModel(array(
            'tag'     => $tag,
            'threads' => $threads,
            'form'    => $form
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

        // Create new form instance.
        $form = $this->getServiceLocator()->get('edpdiscuss_form');
        
        return new ViewModel(array(
            'tag'      => $tag,
            'thread'   => $thread,
            'messages' => $messages,
            'form'     => $form
        ));
    }

    public function newmessageAction()
    {
        // Create new form and hydrator instances.
        $form = $this->getServiceLocator()->get('edpdiscuss_form');
        $formHydrator = $this->getServiceLocator()->get('edpdiscuss_post_form_hydrator');
        
        $tag = $this->getTag();
        $thread = $this->getThread();
        
        // Check if the request is a POST.
        $request = $this->getRequest();
        if ($request->isPost())
        {
            // POST, so check if valid.
            $data = (array) $request->getPost();
          
            // create a new message and sets its thread.
            $message = $this->getServiceLocator()->get('edpdiscuss_message');
            $message->setThread($thread);
        
            $form->setHydrator($formHydrator);
            $form->bind($message);
            $form->setData($data);
            if ($form->isValid())
            {
          	    // Persist message.
            	$this->getDiscussService()->createMessage($message);
                
            	// Redirect to list of messages
		        return $this->redirect()->toRoute('edpforum/thread', array(
		            'tagslug'    => $tag->getSlug(),
                    'tagid'      => $tag->getTagId(),
                    'threadslug' => $thread->getSlug(),
                    'threadid'   => $thread->getThreadId(),
		            'action'     => 'messages'
		        ));
            }
        } 
        
        // If not a POST request, then just render the form.
        return new ViewModel(array(
            'form'   => $form,
            'tag'    => $tag,
            'thread' => $thread
        ));
          
    }
    
    public function newthreadAction()
    {
    	// Create new form instance.
        $form = $this->getServiceLocator()->get('edpdiscuss_form');
        $formHydrator = $this->getServiceLocator()->get('edpdiscuss_post_form_hydrator');
        
        $tag = $this->getTag();
        
        // Check if the request is a POST.
        $request = $this->getRequest();
        if ($request->isPost())
        {
    	    // if post, check if valid
            $data = (array) $request->getPost();
            
            // create a new thread and sets its tag.
            $thread = $this->getServiceLocator()->get('edpdiscuss_thread');
            
            // Create a new message and set its thread.
            $message = $this->getServiceLocator()->get('edpdiscuss_message');
            $message->setThread($thread);
            
            $form->setHydrator($formHydrator);
            $form->bind($message);
            $form->setData($data);
            if ($form->isValid())
            {
                // Persist message and thread.
                $thread = $this->getDiscussService()->createThread($thread, $message);
                
                // Associate thread with tag.
                $this->getDiscussService()->AssociateTagAndThread($tag, $thread);
                
                // Redirect to list of messages
                return $this->redirect()->toRoute('edpforum/thread', array(
                    'tagslug'    => $tag->getSlug(),
                    'tagid'      => $tag->getTagId(),
                    'threadslug' => $thread->getSlug(),
                    'threadid'   => $thread->getThreadId(),
                    'action'     => 'messages'
                ));
            } 
        }
        
        // If not a POST request, then just render the form.
        return new ViewModel(array(
            'form'   => $form,
            'tag'    => $tag
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
        if (null === $this->discussService) {
            $this->discussService = $this->getServiceLocator()->get('edpdiscuss_discuss_service');
        }

        return $this->discussService;
    }

    public function setDiscussService($discussService)
    {
        $this->discussService = $discussService;
        return $this;
    }
}
