<?php


namespace Pina\Http;

use Pina\App;
use Pina\Arr;
use Pina\Controls\BreadcrumbView;
use Pina\Paging;
use Pina\Request;
use Pina\Response;
use Pina\NotFoundException;
use Pina\TableDataGateway;
use Pina\Data\Schema;
use Pina\Data\DataRecord;
use Pina\Data\DataTable;
use Pina\Controls\ButtonRow;
use Pina\Controls\FilterForm;
use Pina\Controls\LinkedButton;
use Pina\Controls\PagingControl;
use Pina\Controls\RecordForm;
use Pina\Controls\RecordView;
use Pina\Controls\SidebarWrapper;
use Pina\Controls\TableView;
use Pina\Components\DefaultExport;
use Pina\Components\SelectComponent;

use function Pina\__;

abstract class CollectionEndpoint extends Endpoint
{
    protected $exportAllowed = false;

    /** @return Schema */
    abstract function getFilterSchema();

    /** @return Schema */
    abstract function getListSchema();

    /** @return Schema */
    abstract function getSchema();

    /** @return Schema */
    abstract function getCreationSchema();

    /** @return TableDataGateway */
    abstract function makeQuery();

    /** @return string */
    abstract function getCollectionTitle();

    /**
     * @param array $item
     * @return string
     */
    abstract function getItemTitle($item);

    /** @return string */
    abstract function getCreationTitle();


    /**
     * @param string $event
     * @param int $id
     */
    abstract function trigger($event, $id);

    /**
     * @return TableView
     * @throws \Exception
     */
    public function index()
    {
        $filters = Arr::only($this->query()->all(), $this->getFilterSchema()->getFieldKeys());

        $this->exportIfNeeded($filters);

        $query = $this->makeIndexQuery($filters);

        if ($this->attributes()->get('display')) {
            return $this->drawIndexAsSelect($query);
        }

        Request::setPlace('page_header', $this->getCollectionTitle());
        Request::setPlace('breadcrumb', $this->getBreadcrumb($this->getCollectionTitle())->drawWithWrappers());

        $paging = $this->applyPaging($query, $filters);
        return $this->makeCollectionView()
            ->after($paging)
            ->after($this->makeIndexButtons())
            ->wrap($this->makeSidebarWrapper()->setSidebar($this->makeFilterForm()))
            ->load(new DataTable($query->get(), $this->getListSchema()));
    }

    public function show($id)
    {
        $item = $this->makeShowQuery()->findOrFail($id);

        $title = $this->getItemTitle($item);
        Request::setPlace('page_header', $title);
        Request::setPlace('breadcrumb', $this->getBreadcrumb($this->getCollectionTitle(), $title)->drawWithWrappers());

        return $this->makeRecordView()
            ->wrap($this->makeSidebarWrapper())
            ->load(new DataRecord($item, $this->getSchema()));
    }


    public function create()
    {
        Request::setPlace('page_header', $this->getCreationTitle());
        Request::setPlace(
            'breadcrumb',
            $this->getBreadcrumb($this->getCollectionTitle(), $this->getCreationTitle())->drawWithWrappers()
        );

        return $this->makeCreateForm()
            ->wrap($this->makeSidebarWrapper())
            ->load(new DataRecord([], $this->getCreationSchema()));
    }

    public function store()
    {
        $data = $this->request()->all();

        return $this->normalizeAndStore($data, $this->getCreationSchema());
    }

    public function update($id)
    {
        $data = $this->request()->all();

        return $this->normalizeAndUpdate($data, $this->getSchema(), $id);
    }

    public function updateSortable()
    {
        $ids = $this->request()->get('id');

        $this->makeQuery()->reorder($ids);

        return Response::ok()->emptyContent();
    }

    /**
     * @param array $filters
     * @throws \Exception
     */
    protected function exportIfNeeded($filters)
    {
        $extension = pathinfo($this->location->link('@'), PATHINFO_EXTENSION);
        if (empty($extension)) {
            return;
        }

        if (!$this->exportAllowed) {
            throw new NotFoundException();
        }

        /** @var DefaultExport $export */
        $export = App::load(DefaultExport::class);
        $export->setFilename($this->getCollectionTitle());
        $export->load($this->makeExportQuery($filters)->get(), $this->getExportSchema());
        $export->download();
        exit;
    }

    /**
     * @param TableDataGateway $query
     * @return SelectComponent
     * @throws \Exception
     */
    protected function drawIndexAsSelect(TableDataGateway $query)
    {
        $placeholder = $this->attributes()->get('placeholder');
        $name = $this->attributes()->get('name');
        return $this->makeSelect()
            ->setName($name)
            ->setPlaceholder($placeholder ? $placeholder : __('Выберите'))
            ->setValue($this->query()->get($name))
            ->load($query->get(), $this->getListSchema());
    }

    protected function normalizeAndStore($data, $schema)
    {
        $normalized = $this->normalize($data, $schema);

        $id = $this->makeQuery()->insertGetId($normalized);

        $this->trigger('created', $id);

        return Response::ok()->contentLocation($this->base->link('@/:id', ['id' => $id]));
    }

    protected function normalizeAndUpdate($data, $schema, $id)
    {
        $normalized = $this->normalize($data, $schema, $id);

        $this->makeQuery()->whereId($id)->update($normalized);

        $this->trigger('updated', $id);

        return Response::ok();
    }

    protected function normalize($data, Schema $schema, $id = null)
    {
        return $schema->normalize($data);
    }

    /**
     * @return TableView
     */
    protected function makeCollectionView()
    {
        return App::make(TableView::class);
    }

    protected function makeRecordView()
    {
        $display = $this->query()->get('display');
        $component = $display == 'edit' ? $this->makeEditForm() : $this->makeViewForm();
        return $component;
    }

    protected function makeEditForm()
    {
        /** @var RecordForm $form */
        $form = App::make(RecordForm::class);
        $form->setMethod('put')->setAction($this->location->link('@'));
        $form->getButtonRow()->append($this->makeCancelButton());
        return $form;
    }

    protected function makeViewForm()
    {
        return App::make(RecordView::class)->after($this->makeViewButtonRow());
    }

    protected function makeCreateForm()
    {
        /** @var RecordForm $form */
        $form = App::make(RecordForm::class);
        $form->setMethod('post')->setAction($this->base->link('@'));
        return $form;
    }

    protected function makeFilterForm()
    {
        /** @var FilterForm $form */
        $form = App::make(FilterForm::class);
        $schema = $this->getFilterSchema();
        $normalized = $schema->normalize($this->query()->all());
        $form->load(new DataRecord($normalized, $schema));
        $form->getButtonRow()->append($this->makeCreateButton());
        return $form;
    }

    /**
     *
     * @return SidebarWrapper
     */
    protected function makeSidebarWrapper()
    {
        return App::make(SidebarWrapper::class);
    }

    protected function makeIndexButtons()
    {
        /** @var ButtonRow $buttons */
        $buttons = App::make(ButtonRow::class);
        $buttons->setMain($this->makeCreateButton()->setStyle('primary'));
        if ($this->exportAllowed) {
            $buttons->append($this->makeExportButton());
        }
        return $buttons;
    }

    protected function makeExportButton()
    {
        /** @var DefaultExport $export */
        $export = App::load(DefaultExport::class);
        /** @var LinkedButton $buttons */
        $btn = App::make(LinkedButton::class);
        $btn->setLink($this->base->link('@.' . $export->getExtension(), $this->query()->all()));
        $btn->setTitle(__('Скачать'));

        return $btn;
    }

    protected function makeCancelButton()
    {
        /** @var LinkedButton $button */
        $button = App::make(LinkedButton::class);
        $button->setLink($this->location->link('@'));
        $button->setTitle(__('Отменить'));
        return $button;
    }

    protected function makeCreateButton()
    {
        $button = App::make(LinkedButton::class);
        $button->setLink($this->base->link('@/create'));
        $button->setTitle(__('Добавить'));
        return $button;
    }

    /**
     * @return ButtonRow
     */
    protected function makeViewButtonRow()
    {
        /** @var ButtonRow $row */
        $row = App::make(ButtonRow::class);
        $row->addClass('mb-5');
        $row->setMain($this->makeEditLinkButton());
        return $row;
    }

    protected function makeEditLinkButton()
    {
        $button = App::make(LinkedButton::class);
        $button->setLink($this->location->link('@', ['display' => 'edit']));
        $button->setStyle('primary');
        $button->setTitle(__('Редактировать'));
        return $button;
    }

    /**
     * @return SelectComponent
     */
    protected function makeSelect()
    {
        return App::make(SelectComponent::class);
    }

    protected function getBreadcrumb($baseTitle = '', $title = null)
    {
        $path = [];
        $path[] = ['title' => '<i class="mdi mdi-home"></i>', 'link' => $this->base->link('/')];
        $path[] = ['title' => $baseTitle, 'link' => $this->base->link('@')];
        if ($title) {
            $path[] = ['title' => $title, 'is_active' => true];
        }
        $view = App::make(BreadcrumbView::class);
        $view->load(new DataTable($path, new Schema()));
        return $view;
    }

    /**
     * @param TableDataGateway $query
     * @param array $filters
     * @return PagingControl
     */
    protected function applyPaging($query, $filters)
    {
        $paging = new Paging($this->request()->get('page'), $this->request()->get("paging", 25));
        $query->paging($paging);

        $pagingControl = new PagingControl();
        $pagingControl->init($paging);
        $pagingControl->setLinkContext($filters);

        return $pagingControl;
    }

    /**
     * @param array $filters
     * @return TableDataGateway
     */
    protected function makeIndexQuery($filters)
    {
        return $this->addIndexQueryColumns($this->makeFilteredQuery($filters));
    }

    /**
     * @param array $filters
     * @return TableDataGateway
     */
    protected function makeExportQuery($filters)
    {
        return $this->addExportQueryColumns($this->makeFilteredQuery($filters));
    }

    /**
     * @return TableDataGateway
     */
    protected function makeShowQuery()
    {
        return $this->addShowQueryColumns($this->makeQuery());
    }

    /**
     * @param TableDataGateway $query
     * @return TableDataGateway
     */
    protected function addIndexQueryColumns($query)
    {
        return $this->addDefaultQueryColumns($query);
    }

    /**
     * @param TableDataGateway $query
     * @return TableDataGateway
     */
    protected function addExportQueryColumns($query)
    {
        return $this->addIndexQueryColumns($query);
    }

    /**
     * @param TableDataGateway $query
     * @return TableDataGateway
     */
    protected function addShowQueryColumns($query)
    {
        return $this->addDefaultQueryColumns($query);
    }

    /**
     * @param TableDataGateway $query
     * @return TableDataGateway
     */
    protected function addDefaultQueryColumns($query)
    {
        return $query;
    }

    protected function makeFilteredQuery($filters)
    {
        $schema = $this->getFilterSchema();
        $gw = $this->makeQuery();
        $availableFields = array_keys($gw->getFields());
        foreach ($schema->getIterator() as $field) {
            if (!in_array($field->getKey(), $availableFields)) {
                continue;
            }
            $value = isset($filters[$field->getKey()]) ? $filters[$field->getKey()] : '';
            if (empty($value)) {
                continue;
            }
            $type = $field->getType();
            if ($type == 'string') {
                $gw->whereLike($field->getKey(), '%' . $value . '%');
            } else {
                $gw->whereBy($field->getKey(), $value);
            }
        }

        return $gw;
    }


    protected function getExportSchema()
    {
        return $this->getListSchema();
    }

}
