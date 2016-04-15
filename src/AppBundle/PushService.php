<?php
namespace AppBundle;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class PushService implements WampServerInterface {

    protected $subscribedTopics = array();

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        echo "Client subscribing to $topic\n";
        $this->subscribedTopics[$topic->getId()] = $topic;
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onNewEvent($entry) {

      // If the lookup topic object isn't set there is no one to publish to
      if (!array_key_exists($entry, $this->subscribedTopics)) {
          return;
      }

      $topic = $this->subscribedTopics[$entry];

      // re-send the data to all the clients subscribed to that category
      $topic->broadcast($entry);
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
    }

    public function onOpen(ConnectionInterface $conn) {
    }

    public function onClose(ConnectionInterface $conn) {
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}
