<?php

namespace Pina\Composers;

use Exception;
use Pina\App;
use Pina\Controls\BreadcrumbView;
use Pina\Data\DataTable;
use Pina\Data\DataRecord;
use Pina\Data\Schema;
use Pina\Model\LinkedItem;
use Pina\Model\LinkedItemCollection;
use Pina\Request;
use Pina\Http\Location;

use function \Pina\__;

class CollectionComposer
{
    protected $collection;
    protected $creation;
    protected $itemCallback;

    public function __construct()
    {
        $this->collection = __('Список');
        $this->creation = __('Новый элемент');
    }

    public function configure(string $collection, string $creation)
    {
        $this->collection = $collection;
        $this->creation = $creation;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setItemCallback(Callable $callback)
    {
        $this->itemCallback = $callback;
    }

    public function index(Location $location)
    {
        $links = $this->getParentLinks($location->location('@@'));
        $links->add(new LinkedItem($this->collection, $location->link('@')));

        Request::setPlace('page_header', $this->collection);
        Request::setPlace('breadcrumb', $this->getBreadcrumb($links));
    }

    public function show(Location $location, DataRecord $record)
    {
        $links = $this->getParentLinks($location->location('@@@'));
        $links->add(new LinkedItem($this->collection, $location->link('@@')));

        $title = $this->getItemTitle($record);
        $links->add(new LinkedItem($title, $location->link('@')));

        Request::setPlace('page_header', $title);
        Request::setPlace('breadcrumb', $this->getBreadcrumb($links));
    }

    public function create(Location $location)
    {
        $links = $this->getParentLinks($location->location('@@@'));
        $links->add(new LinkedItem($this->collection, $location->link('@@')));
        $links->add(new LinkedItem($this->creation, $location->link('@')));

        Request::setPlace('page_header', $this->creation);
        Request::setPlace('breadcrumb', $this->getBreadcrumb($links));
    }

    public function section(Location $location, DataRecord $record, string $section)
    {
        $links = $this->getParentLinks($location->location('@@@@'));
        $links->add(new LinkedItem($this->collection, $location->link('@@@')));
        $links->add(new LinkedItem($this->getItemTitle($record), $location->link('@@')));
        $links->add(new LinkedItem($section, $location->link('@')));

        Request::setPlace('page_header', $section);
        Request::setPlace('breadcrumb', $this->getBreadcrumb($links));
    }

    public function getItemTitle(DataRecord $record)
    {
        if ($this->itemCallback) {
            $fn = $this->itemCallback;
            return $title = $fn($record);
        }
        $title = $record->getMeta('title');
        if (empty($title)) {
            $title = $record->getMeta('id');
        }
        return trim($title);
    }

    protected function getParentLinks(Location $location): LinkedItemCollection
    {
        if (!$location->resource('@')) {
            $links = new LinkedItemCollection();
            try {
                $title = App::router()->run('/', 'title');
                if ($title) {
                    $links->add(new LinkedItem($title, '/'));
                }
            } catch (Exception $e) {
            }
            return $links;
        }

        $links = $this->getParentLinks($location->location('@@'));

        try {
            $title = App::router()->run($location->resource('@'), 'title');
            if ($title) {
                $links->add(new LinkedItem($title, $location->link('@')));
            }
        } catch (Exception $e) {
        }
        return $links;
    }

    protected function getBreadcrumb(LinkedItemCollection $links)
    {
        $path = [];
        foreach ($links as $link) {
            $path[] = [
                'title' => $link->getTitle(),
                'link' => $link->getLink(),
                'is_active' => false
            ];
        }
//        array_unshift($path, ['title' => 'Home', 'link' => App::link('/')]);

        $path[count($path) - 1]['is_active'] = true;
        $path[count($path) - 1]['link'] = null;

        $view = App::make(BreadcrumbView::class);
        $view->load(new DataTable($path, new Schema()));
        return $view;
    }


}