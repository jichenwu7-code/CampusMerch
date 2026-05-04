<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    private $rowCount = 0;

    public function model(array $row)
    {
        $this->rowCount++;
        
        return new Product([
            'name' => $row['商品名称'],
            'category' => $row['分类'],
            'price' => $row['价格'],
            'stock' => $row['库存'] ?? 0,
            'status' => $row['状态'] ?? 1,
            'cover_url' => $row['封面图'] ?? null,
            'custom_rule' => $row['定制规则'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            '商品名称' => 'required|string|max:255',
            '分类' => 'required|string|max:100',
            '价格' => 'required|numeric|min:0',
            '库存' => 'nullable|integer|min:0',
            '状态' => 'nullable|integer|in:0,1',
            '封面图' => 'nullable|string|max:500',
            '定制规则' => 'nullable|string|max:1000',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}