<?php

namespace App\Enums;

enum Role: string
{
    case LabAdmin = 'lab_admin';
    case Receptionist = 'receptionist';
    case Phlebotomist = 'phlebotomist';
    case Technician = 'technician';
    case Pathologist = 'pathologist';
    case Cashier = 'cashier';
    case Auditor = 'auditor';

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        $read = [
            'patient.read',
            'order.read',
            'catalogue.read',
            'specimen.read',
            'result.read',
            'report.read',
            'report.download',
        ];

        return match ($this) {
            self::LabAdmin => ['*'],
            self::Receptionist => [...$read, 'patient.write', 'order.create', 'invoice.write'],
            self::Phlebotomist => [...$read, 'specimen.collect', 'barcode.print'],
            self::Technician => [...$read, 'order.create', 'specimen.receive', 'result.enter', 'result.review'],
            self::Pathologist => [
                ...$read,
                'order.create',
                'result.enter',
                'result.review',
                'result.validate',
                'report.release',
                'report.correct',
                'report.withdraw',
                'critical.acknowledge',
                'template.manage',
            ],
            self::Cashier => [...$read, 'invoice.write'],
            self::Auditor => $read,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::LabAdmin => 'Lab admin',
            self::Receptionist => 'Reception',
            self::Phlebotomist => 'Phlebotomist',
            self::Technician => 'Technician',
            self::Pathologist => 'Pathologist',
            self::Cashier => 'Cashier',
            self::Auditor => 'Auditor',
        };
    }
}
