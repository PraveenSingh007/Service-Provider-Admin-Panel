<?php

declare(strict_types=1);

namespace App\Admin\Model;

/**
 * Model representing a Callback Request entry.
 */
class CallbackRequest
{
    private ?int $id;
    private string $callbackNo;
    private string $customerName;
    private string $mobileNo;
    private string $serviceCategory;
    private string $preferredTimeSlot;
    private ?string $note;
    private string $status;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $callbackNo,
        string $customerName,
        string $mobileNo,
        string $serviceCategory = 'other',
        string $preferredTimeSlot = 'anytime',
        ?string $note = null,
        string $status = 'pending',
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->callbackNo = $callbackNo;
        $this->customerName = $customerName;
        $this->mobileNo = $mobileNo;
        $this->serviceCategory = $serviceCategory;
        $this->preferredTimeSlot = $preferredTimeSlot;
        $this->note = $note;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCallbackNo(): string
    {
        return $this->callbackNo;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getMobileNo(): string
    {
        return $this->mobileNo;
    }

    public function getServiceCategory(): string
    {
        return $this->serviceCategory;
    }

    public function getPreferredTimeSlot(): string
    {
        return $this->preferredTimeSlot;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            (string) ($data['callback_no'] ?? ''),
            (string) ($data['customer_name'] ?? ''),
            (string) ($data['mobile_no'] ?? ''),
            (string) ($data['service_category'] ?? 'other'),
            (string) ($data['preferred_time_slot'] ?? 'anytime'),
            isset($data['note']) ? (string) $data['note'] : null,
            (string) ($data['status'] ?? 'pending'),
            isset($data['created_at']) ? (string) $data['created_at'] : null,
            isset($data['updated_at']) ? (string) $data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'callback_no' => $this->callbackNo,
            'customer_name' => $this->customerName,
            'mobile_no' => $this->mobileNo,
            'service_category' => $this->serviceCategory,
            'preferred_time_slot' => $this->preferredTimeSlot,
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
