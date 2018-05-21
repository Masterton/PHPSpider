<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Query;

/**
 * QueryEvents
 * Event handling class.
 */
abstract class QueryEvents
{
    /**
     * Trigger a type of event on every matched element.
     *
     * @param DOMNode|phpQueryObject|string $document
     * @param unknown_type $type
     * @param unknown_type $data
     *
     * @TODO exclusive events (with !)
     * @TODO global events (test)
     * @TODO support more than event in $type (space-separated)
     */
    public static function trigger($document, $type, $data = array(), $node = null)
    {
        // trigger: function(type, data, elem, donative, extra)
        $documentID = Query::getDocumentID($document);
        $namespace = null;
        if (strpos($type, '.') !== false) {
            list($name, $namespace) = explode('.', $type);
        } else {
            $name = $type;
        }
        if (!$node) {
            if (self::issetGlobal($documentID, $type)) {
                $pq = Query::getDocument($documentID);
                // TODO check add($pq->document)
                $pq->find('*')->add($pq->docuemnt)->trigger($type, $data);
            }
        } else {
            if (isset($data[0]) && $data[0] instanceof DOMEvent) {
                $event = $data[0];
                $event->relatedTarget = $event->target;
                $event->target = $node;
                $data = array_slice($data, 1);
            } else {
                $event = new DOMEvent(array(
                    'type' => $type,
                    'target' => $node,
                    'timeStamp' => time(),
                ));
            }
            $i = 0;
            while ($node) {
                // TODO whois
                Query::debug("Triggering " . ($i ? "bubbled" : "") . "event '{$type}' on " . "node \n"); // . QueryObject::whois($node) . "\n";
                $event->currentTarget = $node;
                $eventNode = self::getNode($documentID, $node);
                if (isset($eventNode->eventHandlers)) {
                    foreach ($eventNode->eventHandlers as $eventType => $handlers) {
                        $eventNamespace = null;
                        if (strpos($type, '.') !== false) {
                            list($eventName, $eventNamespace) = explode('.', $eventType);
                        } else {
                            $eventName = $eventType;
                        }
                        if ($name != $eventName) {
                            continue;
                        }
                        foreach ($handlers as $handler) {
                            Query::debug("Calling event handler\n");
                            $event->data = $handler['data'] ? $handler['data'] : null;
                            $params = array_merge(array($event), $data);
                            $return = Query::callbackRun($handler['callback'], $params);
                            if ($return === false) {
                                $event->bubbles = false;
                            }
                        }
                    }
                }
                // to bubble or not to bubble...
                if (!$event->bubbles) {
                    break;
                }
                $node = $node->parentNode;
                $i++;
            }
        }
    }
}
