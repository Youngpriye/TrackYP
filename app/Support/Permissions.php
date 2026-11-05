<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Central catalogue of TrackYP roles and permissions.
 *
 * This class is the single source of truth. It only describes names and
 * mappings. It never touches the database, creates users, seeds records
 * or grants authorization bypasses.
 */
final class Permissions
{
    /** Authentication guard used by every role and permission. */
    public const GUARD = 'web';

    // ------------------------------------------------------------------
    // Role names
    // ------------------------------------------------------------------

    public const ROLE_SUPER_ADMIN = 'Super Admin';
    public const ROLE_MANAGER = 'Manager';
    public const ROLE_DISPATCHER = 'Dispatcher';
    public const ROLE_ACCOUNTANT = 'Accountant';
    public const ROLE_DELIVERY_PERSONNEL = 'Delivery Personnel';

    /** Every role, in display order. */
    private const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_DISPATCHER,
        self::ROLE_ACCOUNTANT,
        self::ROLE_DELIVERY_PERSONNEL,
    ];

    // ------------------------------------------------------------------
    // Permission names
    // ------------------------------------------------------------------

    public const DASHBOARD_VIEW = 'dashboard.view';

    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DEACTIVATE = 'users.deactivate';
    public const USERS_RESET_PASSWORD = 'users.reset-password';

    public const ROLES_VIEW = 'roles.view';
    public const ROLES_MANAGE = 'roles.manage';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const SHIPMENTS_VIEW = 'shipments.view';
    public const SHIPMENTS_CREATE = 'shipments.create';
    public const SHIPMENTS_UPDATE = 'shipments.update';
    public const SHIPMENTS_UPDATE_STATUS = 'shipments.update-status';

    public const DELIVERIES_ASSIGN = 'deliveries.assign';
    public const DELIVERIES_VIEW_OWN = 'deliveries.view-own';
    public const DELIVERIES_UPDATE_OWN_STATUS = 'deliveries.update-own-status';

    public const CUSTOMERS_MANAGE = 'customers.manage';
    public const DELIVERY_PERSONNEL_MANAGE = 'delivery-personnel.manage';

    public const PAYMENTS_VIEW = 'payments.view';
    public const PAYMENTS_RECORD = 'payments.record';

    public const INVOICES_MANAGE = 'invoices.manage';

    public const REPORTS_FINANCIAL_VIEW = 'reports.financial.view';
    public const REPORTS_OPERATIONAL_VIEW = 'reports.operational.view';

    /**
     * The complete permission catalogue.
     * Every permission must be listed here to be registered.
     */
    private const ALL = [
        self::DASHBOARD_VIEW,

        self::USERS_VIEW,
        self::USERS_CREATE,
        self::USERS_UPDATE,
        self::USERS_DEACTIVATE,
        self::USERS_RESET_PASSWORD,

        self::ROLES_VIEW,
        self::ROLES_MANAGE,

        self::SETTINGS_MANAGE,

        self::SHIPMENTS_VIEW,
        self::SHIPMENTS_CREATE,
        self::SHIPMENTS_UPDATE,
        self::SHIPMENTS_UPDATE_STATUS,

        self::DELIVERIES_ASSIGN,
        self::DELIVERIES_VIEW_OWN,
        self::DELIVERIES_UPDATE_OWN_STATUS,

        self::CUSTOMERS_MANAGE,
        self::DELIVERY_PERSONNEL_MANAGE,

        self::PAYMENTS_VIEW,
        self::PAYMENTS_RECORD,

        self::INVOICES_MANAGE,

        self::REPORTS_FINANCIAL_VIEW,
        self::REPORTS_OPERATIONAL_VIEW,
    ];

    /**
     * Explicit permissions for every role EXCEPT Super Admin.
     * Super Admin is deliberately absent: it is derived from ALL.
     */
    private const ROLE_PERMISSIONS = [
        self::ROLE_MANAGER => [
            self::DASHBOARD_VIEW,
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::ROLES_VIEW,
            self::SHIPMENTS_VIEW,
            self::SHIPMENTS_CREATE,
            self::SHIPMENTS_UPDATE,
            self::SHIPMENTS_UPDATE_STATUS,
            self::DELIVERIES_ASSIGN,
            self::CUSTOMERS_MANAGE,
            self::DELIVERY_PERSONNEL_MANAGE,
            self::PAYMENTS_VIEW,
            self::REPORTS_FINANCIAL_VIEW,
            self::REPORTS_OPERATIONAL_VIEW,
        ],

        self::ROLE_DISPATCHER => [
            self::DASHBOARD_VIEW,
            self::SHIPMENTS_VIEW,
            self::SHIPMENTS_UPDATE_STATUS,
            self::DELIVERIES_ASSIGN,
        ],

        self::ROLE_ACCOUNTANT => [
            self::DASHBOARD_VIEW,
            self::PAYMENTS_VIEW,
            self::PAYMENTS_RECORD,
            self::INVOICES_MANAGE,
            self::REPORTS_FINANCIAL_VIEW,
        ],

        self::ROLE_DELIVERY_PERSONNEL => [
            self::DELIVERIES_VIEW_OWN,
            self::DELIVERIES_UPDATE_OWN_STATUS,
        ],
    ];

    /** This class only holds static helpers and must not be instantiated. */
    private function __construct()
    {
    }

    /**
     * Every registered permission name.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return self::ALL;
    }

    /**
     * Every role name, in display order.
     *
     * @return list<string>
     */
    public static function roles(): array
    {
        return self::ROLES;
    }

    /**
     * The permissions assigned to one role.
     *
     * Super Admin always receives the full catalogue, so any permission
     * added to ALL in the future is included automatically.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException when the role name is unknown
     */
    public static function forRole(string $role): array
    {
        if ($role === self::ROLE_SUPER_ADMIN) {
            return self::all();
        }

        if (! array_key_exists($role, self::ROLE_PERMISSIONS)) {
            throw new InvalidArgumentException("Unknown role: {$role}");
        }

        return self::ROLE_PERMISSIONS[$role];
    }

    /**
     * The complete role-to-permissions map, one entry per role.
     *
     * @return array<string, list<string>>
     */
    public static function map(): array
    {
        $map = [];

        foreach (self::ROLES as $role) {
            $map[$role] = self::forRole($role);
        }

        return $map;
    }
}