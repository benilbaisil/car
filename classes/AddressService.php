<?php
declare(strict_types=1);

require_once __DIR__ . '/Address.php';
require_once __DIR__ . '/AddressRepository.php';

/**
 * AddressService class - handles address business logic
 */
class AddressService
{
    private AddressRepository $repository;

    public function __construct(AddressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create and save a new address
     */
    public function createAddress(array $data, int $userId): array
    {
        // Sanitize input data
        $name = trim($data['name'] ?? '');
        $streetAddress = trim($data['street_address'] ?? '');
        $city = trim($data['city'] ?? '');
        $state = trim($data['state'] ?? '');
        $zipCode = trim($data['zip_code'] ?? '');
        $phoneNumber = trim($data['phone_number'] ?? '');

        // Create address object
        $address = new Address(
            $userId,
            $name,
            $streetAddress,
            $city,
            $state,
            $zipCode,
            $phoneNumber
        );

        // Validate address
        $errors = $address->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'address' => null
            ];
        }

        // Save to database
        if ($this->repository->save($address)) {
            return [
                'success' => true,
                'errors' => [],
                'address' => $address
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Failed to save address. Please try again.'],
                'address' => null
            ];
        }
    }

    /**
     * Get user's latest address
     */
    public function getLatestAddress(int $userId): ?Address
    {
        return $this->repository->getLatestByUserId($userId);
    }

    /**
     * Get all addresses for a user
     */
    public function getUserAddresses(int $userId): array
    {
        return $this->repository->getAllByUserId($userId);
    }

    /**
     * Update existing address
     */
    public function updateAddress(Address $address): array
    {
        // Validate address
        $errors = $address->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Update in database
        if ($this->repository->update($address)) {
            return [
                'success' => true,
                'errors' => []
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Failed to update address. Please try again.']
            ];
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress(int $addressId, int $userId): bool
    {
        return $this->repository->delete($addressId, $userId);
    }
}
