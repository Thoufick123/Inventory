<?php
/**
 * Delivery Pipeline Model
 * Consolidates delivery and dispatch tracking across Outwards, Allocations, and Transfers.
 */

class Delivery {

    /**
     * Get unified delivery pipeline records (Allocations, Production Outwards, Transfers).
     */
    public static function getPipeline(array $filters = []): array {
        $deliveries = [];

        // 1. Shop Allocations in dispatch / shipping stage
        $allocations = Database::fetchAll(
            "SELECT wa.id, wa.allocation_number as reference_number, 'ALLOCATION' as type,
                    wa.destination_type, wa.destination_id, wa.status, wa.created_at,
                    w.name as source_warehouse, s.name as destination_name,
                    (SELECT COALESCE(SUM(dispatched_qty), SUM(requested_qty)) FROM allocation_items WHERE allocation_id = wa.id) as item_count
             FROM warehouse_allocations wa
             JOIN warehouses w ON wa.warehouse_id = w.id
             LEFT JOIN shops s ON (wa.destination_type = 'SHOP' AND wa.destination_id = s.id)
             WHERE wa.status IN ('APPROVED', 'PICKING', 'PACKING', 'DISPATCHED', 'COMPLETED')
             ORDER BY wa.created_at DESC"
        );

        foreach ($allocations as $a) {
            $deliveries[] = [
                'id' => $a['id'],
                'type' => 'Shop Allocation',
                'reference_number' => $a['reference_number'],
                'source' => $a['source_warehouse'],
                'destination' => $a['destination_name'] ?? $a['destination_type'],
                'item_count' => (int)$a['item_count'],
                'status' => $a['status'],
                'created_at' => $a['created_at'],
                'action_url' => 'warehouse/allocation/view/' . $a['id']
            ];
        }

        // 2. Production Outwards
        $outwards = Database::fetchAll(
            "SELECT po.id, po.outward_number as reference_number, 'PRODUCTION_OUTWARD' as type,
                    po.dispatch_date, po.vehicle_number, po.created_at,
                    pb.batch_number, pb.total_qty as item_count, pb.status as batch_status,
                    w.name as destination_name
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             ORDER BY po.created_at DESC"
        );

        foreach ($outwards as $o) {
            $deliveries[] = [
                'id' => $o['id'],
                'type' => 'Production Outward',
                'reference_number' => $o['reference_number'] . " (Batch " . $o['batch_number'] . ")",
                'source' => 'Production Facility',
                'destination' => $o['destination_name'],
                'item_count' => (int)$o['item_count'],
                'status' => $o['batch_status'] === 'DISPATCHED' ? 'IN_TRANSIT' : $o['batch_status'],
                'created_at' => $o['created_at'],
                'action_url' => 'warehouse/inward/view/' . $o['id']
            ];
        }

        // 3. Inter-warehouse transfers
        $transfers = Database::fetchAll(
            "SELECT wt.id, wt.transfer_number as reference_number, 'TRANSFER' as type,
                    wt.source_type, wt.source_id, wt.destination_type, wt.destination_id,
                    wt.status, wt.created_at,
                    (SELECT COALESCE(SUM(quantity), 0) FROM transfer_items WHERE transfer_id = wt.id) as item_count
             FROM warehouse_transfers wt
             ORDER BY wt.created_at DESC"
        );

        foreach ($transfers as $t) {
            $deliveries[] = [
                'id' => $t['id'],
                'type' => 'Inter-Warehouse Transfer',
                'reference_number' => $t['reference_number'],
                'source' => $t['source_type'] . " #" . $t['source_id'],
                'destination' => $t['destination_type'] . " #" . $t['destination_id'],
                'item_count' => (int)$t['item_count'],
                'status' => $t['status'],
                'created_at' => $t['created_at'],
                'action_url' => 'warehouse/transfers'
            ];
        }

        // Apply status filter if requested
        if (!empty($filters['status'])) {
            $deliveries = array_filter($deliveries, function($d) use ($filters) {
                return $d['status'] === $filters['status'];
            });
        }

        return $deliveries;
    }
}
