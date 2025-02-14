<?php
// vendor/autoload.php 파일을 포함하여 Ratchet 라이브러리를 로드합니다.
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// WebSocket 서버 클래스 정의
class WebSocketServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // 새로운 연결이 열릴 때 실행
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // 클라이언트로부터 메시지를 받았을 때 실행
        echo sprintf('Connection %d sending message "%s" to %d other connection(s)' . "\n"
            , $from->resourceId, $msg, count($this->clients) - 1);

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // 메시지를 보낸 클라이언트를 제외한 모든 클라이언트에게 메시지를 보냅니다.
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // 연결이 닫힐 때 실행
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        // 에러 발생 시 실행
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// WebSocket 서버 실행
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    9000 // 사용할 포트 번호 (웹 호스팅에서 허용하는 포트인지 확인 필요)
);

echo "WebSocket server started on port 9000\n";

$server->run();
