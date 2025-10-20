<?php
declare(strict_types=1);

/**
 * Address class - represents a user's shipping address
 */
class Address
{
    private ?int $id;
    private int $userId;
    private string $name;
    private string $streetAddress;
    private string $city;
    private string $state;
    private string $zipCode;
    private string $phoneNumber;
    private string $createdAt;

    public function __construct(
        int $userId,
        string $name,
        string $streetAddress,
        string $city,
        string $state,
        string $zipCode,
        string $phoneNumber,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->streetAddress = $streetAddress;
        $this->city = $city;
        $this->state = $state;
        $this->zipCode = $zipCode;
        $this->phoneNumber = $phoneNumber;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setStreetAddress(string $streetAddress): void
    {
        $this->streetAddress = $streetAddress;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Get formatted address string
     */
    public function getFormattedAddress(): string
    {
        return sprintf(
            "%s\n%s\n%s, %s %s\nPhone: %s",
            $this->name,
            $this->streetAddress,
            $this->city,
            $this->state,
            $this->zipCode,
            $this->phoneNumber
        );
    }

    /**
     * Validate address data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->name))) {
            $errors[] = 'Name is required';
        }

        if (empty(trim($this->streetAddress))) {
            $errors[] = 'Street address is required';
        }

        if (empty(trim($this->city))) {
            $errors[] = 'City is required';
        }

        if (empty(trim($this->state))) {
            $errors[] = 'State is required';
        }

        if (empty(trim($this->zipCode))) {
            $errors[] = 'Zip code is required';
        }

        if (empty(trim($this->phoneNumber))) {
            $errors[] = 'Phone number is required';
        }

        // Validate phone number format (basic validation)
        if (!empty($this->phoneNumber) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $this->phoneNumber)) {
            $errors[] = 'Please enter a valid phone number';
        }

        return $errors;
    }
}
