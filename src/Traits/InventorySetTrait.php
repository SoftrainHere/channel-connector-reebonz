<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\InventorySet;

trait InventorySetTrait
{
    /**
     * @param InventorySet $inventorySet
     * @return $this
     */
    public function buildCreatePayload(InventorySet $inventorySet): static
    {
        $this->payload = [];
        $this->payload['input']['vendor_id'] = ChannelConnectorFacade::configuration()->meta->vendor_id;
        $this->payload['input']['bar_code'] = $inventorySet->variant->id;
        $this->payload['input']['order_lmt_cnt'] = $inventorySet->available_stock_qty;
        $this->payload['input'] = ['list' => [$this->payload['input']]];
        return $this;
    }
}
