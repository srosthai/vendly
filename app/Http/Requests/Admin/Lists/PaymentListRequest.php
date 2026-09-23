<?php

namespace App\Http\Requests\Admin\Lists;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

class PaymentListRequest extends ListRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array{search: string, status: string, from: string, to: string, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->search(),
            'status' => $this->choice('status', ['all', ...array_map(fn (PaymentStatus $status): string => $status->value, PaymentStatus::cases())]),
            'from' => $this->string('from')->toString(),
            'to' => $this->string('to')->toString(),
            'sort' => $this->choice('sort', ['newest', 'oldest', 'amount'], 'newest'),
        ];
    }

    public function fromDate(): ?CarbonImmutable
    {
        return $this->filled('from') ? CarbonImmutable::createFromFormat('Y-m-d', $this->string('from')->toString())?->startOfDay() : null;
    }

    public function toDate(): ?CarbonImmutable
    {
        return $this->filled('to') ? CarbonImmutable::createFromFormat('Y-m-d', $this->string('to')->toString())?->endOfDay() : null;
    }
}
