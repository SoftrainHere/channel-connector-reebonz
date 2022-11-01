<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Exceptions\Api\NotDistributedProductException;
use App\Models\Features\InventorySet;
use App\Models\Override;

trait InventorySetHandlerTrait
{
    /**
     * @param InventorySet $inventorySet
     * @return $this
     * @throws NotDistributedProductException
     */
    public function buildCreatePayload(InventorySet $inventorySet): static
    {
        if (
            !$inventorySet->variant->override instanceof Override
            || empty($inventorySet->variant->override->id_from_remote)
        ) {
            throw new NotDistributedProductException();
        }

        $this->payload = [
            'input' => [
                "stock_id" => $inventorySet->variant->override->id_from_remote,
                "stock_count" => $inventorySet->available_stock_qty
            ]
        ];
        return $this;
    }
}
