<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CriticalLimit;
use App\Models\Department;
use App\Models\Lab;
use App\Models\LetterheadTemplate;
use App\Models\Patient;
use App\Models\ReferenceInterval;
use App\Models\ReferringDoctor;
use App\Models\ReportTemplate;
use App\Models\ResultDefinition;
use App\Models\SpecimenType;
use App\Models\TestDefinition;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ReportService;
use App\Services\ResultService;
use App\Services\SpecimenService;
use App\Support\Letterhead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LaboratorySeeder extends Seeder
{
    private const ABIM = 'ABIM Laboratory Test Reference Ranges, January 2026';

    private const ABIM_URL = 'https://www.abim.org/media/e2wdwdqu/laboratory-reference-ranges.pdf';

    private const LOINC = 'LOINC codes as published in AHRQ HCUP laboratory code tables and the Regenstrief LOINC unit guide';

    private const NOTE = 'Starter interval for this demonstration catalogue. A laboratory director must confirm it against the local method, analyser and population before clinical use.';

    public function run(): void
    {
        $lab = Lab::query()->create([
            'name' => 'PathLabCA',
            'legal_name' => 'PathLabCA Diagnostics',
            'phone' => '+91 80 4000 1200',
            'email' => 'reports@pathlabca.test',
            'website' => 'https://pathlabca.test',
            'licence_number' => 'KA-LAB-DEMO-2026',
            'address' => '12 Sample Lane, Bengaluru 560001',
        ]);

        $branch = Branch::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Bengaluru Central',
            'code' => 'BLR',
            'phone' => '+91 80 4000 1200',
            'email' => 'blr@pathlabca.test',
            'address' => '12 Sample Lane, Bengaluru 560001',
        ]);

        $departments = [];
        foreach ([
            'haematology' => 'Haematology',
            'coagulation' => 'Coagulation',
            'biochemistry' => 'Biochemistry',
            'endocrinology' => 'Endocrinology',
            'serology' => 'Serology',
            'urinalysis' => 'Urinalysis',
            'microbiology' => 'Microbiology',
            'histopathology' => 'Histopathology',
            'cytology' => 'Cytology',
            'immunology' => 'Immunology',
            'blood_bank' => 'Blood bank',
            'molecular' => 'Molecular',
            'clinical_pathology' => 'Clinical pathology',
            'tumor_markers' => 'Tumor markers',
        ] as $code => $name) {
            $departments[$code] = Department::query()->create([
                'lab_id' => $lab->id,
                'code' => $code,
                'name' => $name,
            ]);
        }

        $specimens = [];
        foreach ([
            ['edta', 'EDTA whole blood', 'Lavender EDTA tube', 'EDTA', 'Mix gently. Do not centrifuge for CBC.'],
            ['citrate', 'Citrated plasma', 'Blue citrate tube', 'Sodium citrate', 'Fill to the line. Transport to coagulation promptly.'],
            ['fluoride', 'Fluoride plasma', 'Grey fluoride tube', 'Sodium fluoride', 'Use for glucose when serum is not validated.'],
            ['serum', 'Serum', 'Gold/red serum tube', 'Clot activator', 'Allow to clot, then centrifuge.'],
            ['urine', 'Urine', 'Sterile urine container', null, 'Midstream or as ordered. Note collection time.'],
            ['stool', 'Stool', 'Stool container', null, 'Fresh sample. Avoid contamination with urine.'],
            ['culture', 'Blood culture bottle', 'Blood culture bottle', null, 'Collect before antibiotics when possible.'],
            ['sputum', 'Sputum', 'Sterile container', null, 'Deep cough specimen. Note quality.'],
            ['tissue', 'Tissue in fixative', 'Formalin container', '10% neutral buffered formalin', 'Confirm site and laterality on the request.'],
            ['aspirate', 'Aspirate / smear', 'Slide / syringe', null, 'Label the site on every slide.'],
            ['csf', 'Cerebrospinal fluid', 'CSF tube', null, 'Label the tube number. Do not use the first drops for cell count.'],
            ['fluid', 'Body fluid', 'Sterile fluid container', null, 'Record the site: pleural, ascitic, synovial or other.'],
            ['semen', 'Semen', 'Sterile wide-mouth container', null, 'Record abstinence and the time of collection.'],
            ['swab', 'Swab', 'Transport swab', null, 'Record the anatomical site.'],
            ['marrow', 'Bone marrow', 'EDTA and smears', 'EDTA', 'Record the aspiration site.'],
            ['bedside', 'Bedside collection', 'No tube', null, 'Perform and time the procedure at the bedside.'],
        ] as $row) {
            $specimens[$row[0]] = SpecimenType::query()->create([
                'code' => $row[0],
                'name' => $row[1],
                'container' => $row[2],
                'additive' => $row[3],
                'handling_instructions' => $row[4],
            ]);
        }

        $tests = $this->catalogue($lab, $departments, $specimens);
        $this->templates($lab, $branch, $departments);
        $staff = $this->staff($lab, $branch);
        $this->letterhead($lab, $staff['sign']);
        ReferringDoctor::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Dr Meera Iyer',
            'qualification' => 'MBBS, MD',
            'registration_number' => 'KMC-DEMO-1001',
        ]);
        $this->demonstrationOrder($staff['admin'], $tests['CBC']);
    }

    /**
     * @param  array<string, Department>  $departments
     * @param  array<string, SpecimenType>  $specimens
     * @return array<string, TestDefinition>
     */
    private function catalogue(Lab $lab, array $departments, array $specimens): array
    {
        $created = [];
        $order = 1;

        foreach ($this->definitions() as $definition) {
            $test = TestDefinition::query()->create([
                'lab_id' => $lab->id,
                'department_id' => $departments[$definition['department']]->id,
                'specimen_type_id' => $specimens[$definition['specimen']]->id,
                'code' => $definition['code'],
                'name' => $definition['name'],
                'loinc' => $definition['loinc'] ?? null,
                'method' => $definition['method'] ?? null,
                'is_panel' => $definition['panel'] ?? false,
                'price_paise' => $definition['price'] ?? 30000,
                'tat_minutes' => $definition['tat'] ?? 240,
                'report_section' => $departments[$definition['department']]->name,
                'display_order' => $order++,
                'collection_notes' => $definition['notes'] ?? null,
                'synonyms' => self::LOINC,
            ]);
            $created[$definition['code']] = $test;

            foreach ($definition['results'] ?? [] as $index => $result) {
                $record = ResultDefinition::query()->create([
                    'test_definition_id' => $test->id,
                    'code' => $result['code'],
                    'label' => $result['label'],
                    'loinc' => $result['loinc'] ?? null,
                    'data_type' => $result['type'],
                    'unit' => $result['unit'] ?? null,
                    'decimals' => $result['decimals'] ?? 0,
                    'allowed_values' => $result['options'] ?? null,
                    'formula' => $result['formula'] ?? null,
                    'display_order' => $index + 1,
                ]);

                foreach ($result['intervals'] ?? [] as $interval) {
                    ReferenceInterval::query()->create([
                        'result_definition_id' => $record->id,
                        'sex' => $interval['sex'] ?? 'any',
                        'lower_bound' => $interval['low'] ?? null,
                        'upper_bound' => $interval['high'] ?? null,
                        'unit' => $result['unit'] ?? null,
                        'source' => $interval['source'] ?? self::ABIM,
                        'source_url' => $interval['url'] ?? self::ABIM_URL,
                        'population_note' => self::NOTE,
                        'approval_status' => 'approved',
                    ]);
                }

                if (isset($result['critical'])) {
                    CriticalLimit::query()->create([
                        'result_definition_id' => $record->id,
                        'sex' => 'any',
                        'lower_bound' => $result['critical']['low'] ?? null,
                        'upper_bound' => $result['critical']['high'] ?? null,
                        'source' => 'Starter panic threshold for workflow testing',
                        'note' => 'Not a substitute for a laboratory-approved critical-value policy.',
                    ]);
                }
            }
        }

        foreach ($this->definitions() as $definition) {
            foreach ($definition['components'] ?? [] as $index => $code) {
                $created[$definition['code']]->components()->attach($created[$code]->id, [
                    'display_order' => $index + 1,
                    'required' => true,
                ]);
            }
        }

        return $created;
    }

    private function letterhead(Lab $lab, User $signatory): void
    {
        LetterheadTemplate::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Laboratory letterhead',
            'design' => Letterhead::defaultsFromLab($lab, $signatory),
            'is_default' => true,
        ]);
    }

    /**
     * @param  array<string, Department>  $departments
     */
    private function templates(Lab $lab, Branch $branch, array $departments): void
    {
        $letterhead = ReportTemplate::query()->create([
            'lab_id' => $lab->id,
            'name' => 'Letterhead left',
            'header_style' => 'branded_left',
            'version' => 1,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $centred = ReportTemplate::query()->create([
            'lab_id' => $lab->id,
            'department_id' => $departments['biochemistry']->id,
            'name' => 'Centred biochemistry',
            'header_style' => 'centered',
            'version' => 1,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $compact = ReportTemplate::query()->create([
            'lab_id' => $lab->id,
            'department_id' => $departments['haematology']->id,
            'name' => 'Compact haematology',
            'header_style' => 'compact',
            'version' => 1,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $branch->update(['report_template_id' => $letterhead->id]);
        $departments['biochemistry']->update(['report_template_id' => $centred->id]);
        $departments['haematology']->update(['report_template_id' => $compact->id]);
    }

    /**
     * @return array<string, User>
     */
    private function staff(Lab $lab, Branch $branch): array
    {
        $users = [];
        foreach ([
            ['admin', 'Lab Admin', 'admin@pathlabca.test', 'lab_admin', 'Laboratory director', 'MD Pathology'],
            ['reception', 'Front Desk', 'reception@pathlabca.test', 'receptionist', 'Receptionist', null],
            ['collect', 'Sample Collector', 'collect@pathlabca.test', 'phlebotomist', 'Phlebotomist', null],
            ['tech', 'Lab Technician', 'tech@pathlabca.test', 'technician', 'Technician', 'DMLT'],
            ['sign', 'Dr A. Sharma', 'sign@pathlabca.test', 'pathologist', 'Authorised signatory', 'MBBS, MD Pathology'],
        ] as [$key, $name, $email, $role, $designation, $qualification]) {
            $users[$key] = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'lab_id' => $lab->id,
                'branch_id' => $branch->id,
                'role' => $role,
                'designation' => $designation,
                'qualification' => $qualification,
            ]);
        }

        return $users;
    }

    private function demonstrationOrder(User $admin, TestDefinition $cbc): void
    {
        $patient = Patient::query()->create([
            'lab_id' => $admin->lab_id,
            'patient_number' => 'PAT00001',
            'name' => 'DEMO Patient A',
            'date_of_birth' => '1990-04-12',
            'sex' => 'male',
            'phone' => '9000000000',
            'is_demo' => true,
        ]);

        $order = app(OrderService::class)->place(
            $admin,
            $patient,
            $admin->branch,
            [$cbc->id],
            'routine',
            ReferringDoctor::query()->firstOrFail()->id,
            'Synthetic fixture for report layout. Not a patient result.',
        );

        $specimens = app(SpecimenService::class);
        foreach ($order->specimens as $specimen) {
            $specimens->collect($specimen, $admin, 'Right arm', 'Acceptable');
            $specimens->receive($specimen->refresh(), $admin, 'Acceptable');
        }

        $values = [
            'HGB' => '13.8', 'RBC' => '4.70', 'HCT' => '41.0', 'MCV' => '87.2', 'MCH' => '29.4',
            'MCHC' => '33.7', 'RDW' => '13.1', 'WBC' => '7.2', 'NEUT' => '58', 'LYMPH' => '32',
            'MONO' => '6', 'EOS' => '3', 'BASO' => '1', 'PLT' => '250',
        ];
        $entries = [];
        $cbc->load('components.resultDefinitions');
        foreach ($cbc->components as $component) {
            foreach ($component->resultDefinitions as $definition) {
                if (isset($values[$definition->code])) {
                    $entries[$definition->id] = ['value' => $values[$definition->code], 'comment' => 'Synthetic fixture'];
                }
            }
        }

        $results = app(ResultService::class);
        $results->save($order, $admin, $entries);
        $results->reviewTechnically($order->refresh(), $admin);
        $results->validateClinically($order->refresh(), $admin);
        app(ReportService::class)->release($order->refresh(), $admin);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        $abim = ['source' => self::ABIM, 'url' => self::ABIM_URL];
        $ccsap = ['source' => 'ACCP CCSAP Reference Values for Common Laboratory Tests', 'url' => 'https://www.accp.com/docs/sap/Lab_Values_Table_CCSAP.pdf'];

        $numeric = function (string $code, string $label, ?string $loinc, ?string $unit, int $decimals, array $intervals, ?array $critical = null, ?string $formula = null) {
            return compact('code', 'label', 'loinc', 'unit', 'decimals', 'intervals', 'critical', 'formula') + ['type' => 'numeric'];
        };

        return array_merge([
            $this->test('HGB', 'Haemoglobin', 'haematology', 'edta', [$numeric('HGB', 'Haemoglobin', '718-7', 'g/dL', 1, [
                ['sex' => 'male', 'low' => 14, 'high' => 18] + $abim,
                ['sex' => 'female', 'low' => 12, 'high' => 16] + $abim,
            ], ['low' => 7, 'high' => 20])], price: 15000),
            $this->test('RBC', 'RBC count', 'haematology', 'edta', [$numeric('RBC', 'RBC count', '789-8', '10^6/µL', 2, [
                ['low' => 4.2, 'high' => 5.9] + $abim,
            ])]),
            $this->test('HCT', 'Haematocrit', 'haematology', 'edta', [$numeric('HCT', 'Haematocrit / PCV', '4544-3', '%', 1, [
                ['sex' => 'male', 'low' => 42, 'high' => 50] + $ccsap,
                ['sex' => 'female', 'low' => 36, 'high' => 45] + $ccsap,
            ])]),
            $this->test('MCV', 'MCV', 'haematology', 'edta', [$numeric('MCV', 'MCV', '787-2', 'fL', 1, [['low' => 80, 'high' => 98] + $abim])]),
            $this->test('MCH', 'MCH', 'haematology', 'edta', [$numeric('MCH', 'MCH', '785-6', 'pg', 1, [['low' => 28, 'high' => 32] + $abim])]),
            $this->test('MCHC', 'MCHC', 'haematology', 'edta', [$numeric('MCHC', 'MCHC', '786-4', 'g/dL', 1, [['low' => 33, 'high' => 36] + $abim])]),
            $this->test('RDW', 'RDW', 'haematology', 'edta', [$numeric('RDW', 'RDW', '788-0', '%', 1, [['low' => 9, 'high' => 14.5] + $abim])]),
            $this->test('WBC', 'Total WBC count', 'haematology', 'edta', [$numeric('WBC', 'Total WBC count', '6690-2', '10^3/µL', 1, [
                ['low' => 4, 'high' => 11] + $abim,
            ], ['low' => 2, 'high' => 30])], notes: 'ABIM leukocyte count 4,000–11,000/µL, stored as 10^3/µL.'),
            $this->test('NEUT', 'Neutrophils', 'haematology', 'edta', [$numeric('NEUT', 'Neutrophils', '770-8', '%', 0, [['low' => 50, 'high' => 70] + $abim])]),
            $this->test('LYMPH', 'Lymphocytes', 'haematology', 'edta', [$numeric('LYMPH', 'Lymphocytes', '736-9', '%', 0, [['low' => 30, 'high' => 45] + $abim])]),
            $this->test('MONO', 'Monocytes', 'haematology', 'edta', [$numeric('MONO', 'Monocytes', '5905-5', '%', 0, [['low' => 0, 'high' => 6] + $abim])]),
            $this->test('EOS', 'Eosinophils', 'haematology', 'edta', [$numeric('EOS', 'Eosinophils', '713-8', '%', 0, [['low' => 0, 'high' => 3] + $abim])]),
            $this->test('BASO', 'Basophils', 'haematology', 'edta', [$numeric('BASO', 'Basophils', '706-2', '%', 0, [['low' => 0, 'high' => 1] + $abim])]),
            $this->test('PLT', 'Platelet count', 'haematology', 'edta', [$numeric('PLT', 'Platelet count', '777-3', '10^3/µL', 0, [
                ['low' => 150, 'high' => 450] + $abim,
            ], ['low' => 20, 'high' => 1000])], notes: 'ABIM platelet count 150,000–450,000/µL, stored as 10^3/µL.'),
            $this->test('CBC', 'Complete Blood Count', 'haematology', 'edta', panel: true, components: ['HGB', 'RBC', 'HCT', 'MCV', 'MCH', 'MCHC', 'RDW', 'WBC', 'NEUT', 'LYMPH', 'MONO', 'EOS', 'BASO', 'PLT'], price: 45000, loinc: '58410-2'),
            $this->test('ESR', 'ESR', 'haematology', 'edta', [$numeric('ESR', 'ESR (Westergren)', '4537-7', 'mm/hr', 0, [
                ['sex' => 'male', 'low' => 0, 'high' => 15] + $abim,
                ['sex' => 'female', 'low' => 0, 'high' => 20] + $abim,
            ])], method: 'Westergren'),
            $this->test('PBS', 'Peripheral Blood Smear', 'haematology', 'edta', [[
                'code' => 'PBS', 'label' => 'Morphology', 'type' => 'narrative', 'loinc' => '11156-7',
            ]], method: 'Microscopy'),
            $this->test('RETIC', 'Reticulocyte count', 'haematology', 'edta', [$numeric('RETIC', 'Reticulocytes', '17849-1', '%', 1, [['low' => 0.5, 'high' => 1.5] + $abim])]),
            $this->test('PT', 'Prothrombin time', 'coagulation', 'citrate', [$numeric('PT', 'Prothrombin time', '5902-2', 'seconds', 1, [['low' => 11, 'high' => 13] + $abim])]),
            $this->test('INR', 'INR', 'coagulation', 'citrate', [$numeric('INR', 'INR', '6301-6', null, 2, [['low' => 0.9, 'high' => 1.1] + $ccsap], ['high' => 5])]),
            $this->test('PTINR', 'PT / INR', 'coagulation', 'citrate', panel: true, components: ['PT', 'INR'], price: 35000),
            $this->test('APTT', 'APTT', 'coagulation', 'citrate', [$numeric('APTT', 'APTT', '14979-9', 'seconds', 1, [['low' => 25, 'high' => 35] + $abim])]),
            $this->test('DDIMER', 'D-Dimer', 'coagulation', 'citrate', [$numeric('DDIMER', 'D-Dimer', '48065-7', 'µg/mL', 2, [['high' => 0.5] + $abim])], notes: 'ABIM lists D-dimer <0.5 µg/mL. Cut-off is assay specific.'),
            $this->test('FBS', 'Fasting blood glucose', 'biochemistry', 'fluoride', [$numeric('FBS', 'Fasting glucose', '1558-6', 'mg/dL', 0, [['low' => 70, 'high' => 99] + $abim], ['low' => 40, 'high' => 500])], notes: 'Record fasting status.'),
            $this->test('RBS', 'Random blood glucose', 'biochemistry', 'fluoride', [$numeric('RBS', 'Random glucose', '2345-7', 'mg/dL', 0, [], ['low' => 40, 'high' => 500])], notes: 'No single random reference interval is applied. Critical limits are starter panic values.'),
            $this->test('PPBS', 'Post-prandial blood glucose', 'biochemistry', 'fluoride', [$numeric('PPBS', 'Post-prandial glucose', '1521-4', 'mg/dL', 0, [])], notes: 'Record the time after the meal. Do not apply the fasting interval.'),
            $this->test('HBA1C', 'HbA1c', 'biochemistry', 'edta', [$numeric('HBA1C', 'HbA1c', '4548-4', '%', 1, [['low' => 4, 'high' => 5.6] + $abim])], method: 'NGSP %'),
            $this->test('TBIL', 'Total bilirubin', 'biochemistry', 'serum', [$numeric('TBIL', 'Bilirubin, total', '1975-2', 'mg/dL', 2, [['low' => 0.3, 'high' => 1] + $abim])]),
            $this->test('DBIL', 'Direct bilirubin', 'biochemistry', 'serum', [$numeric('DBIL', 'Bilirubin, direct', '1968-7', 'mg/dL', 2, [['low' => 0.1, 'high' => 0.3] + $abim])]),
            $this->test('AST', 'AST', 'biochemistry', 'serum', [$numeric('AST', 'AST', '1920-8', 'U/L', 0, [['low' => 10, 'high' => 40] + $abim])]),
            $this->test('ALT', 'ALT', 'biochemistry', 'serum', [$numeric('ALT', 'ALT', '1742-6', 'U/L', 0, [['low' => 10, 'high' => 40] + $abim])]),
            $this->test('ALP', 'Alkaline phosphatase', 'biochemistry', 'serum', [$numeric('ALP', 'ALP', '6768-6', 'U/L', 0, [['low' => 30, 'high' => 120] + $abim])]),
            $this->test('GGT', 'GGT', 'biochemistry', 'serum', [$numeric('GGT', 'GGT', '2324-2', 'U/L', 0, [
                ['sex' => 'male', 'low' => 9, 'high' => 50] + $abim,
                ['sex' => 'female', 'low' => 8, 'high' => 40] + $abim,
            ])]),
            $this->test('TP', 'Total protein', 'biochemistry', 'serum', [$numeric('TP', 'Total protein', '2885-2', 'g/dL', 1, [['low' => 5.5, 'high' => 9] + $abim])]),
            $this->test('ALB', 'Albumin', 'biochemistry', 'serum', [$numeric('ALB', 'Albumin', '1751-7', 'g/dL', 1, [['low' => 3.5, 'high' => 5.5] + $abim])]),
            $this->test('GLOB', 'Globulin', 'biochemistry', 'serum', [$numeric('GLOB', 'Globulin', '2336-6', 'g/dL', 1, [['low' => 2, 'high' => 3.5] + $abim], null, 'protein-albumin')]),
            $this->test('LFT', 'Liver function test', 'biochemistry', 'serum', panel: true, components: ['TBIL', 'DBIL', 'AST', 'ALT', 'ALP', 'GGT', 'TP', 'ALB', 'GLOB'], price: 70000),
            $this->test('UREA', 'Blood urea nitrogen', 'biochemistry', 'serum', [$numeric('UREA', 'BUN', '3094-0', 'mg/dL', 0, [['low' => 8, 'high' => 20] + $abim])]),
            $this->test('CREAT', 'Creatinine', 'biochemistry', 'serum', [$numeric('CREAT', 'Creatinine', '2160-0', 'mg/dL', 2, [
                ['sex' => 'male', 'low' => 0.7, 'high' => 1.3] + $abim,
                ['sex' => 'female', 'low' => 0.5, 'high' => 1.1] + $abim,
            ], ['high' => 7])]),
            $this->test('URIC', 'Uric acid', 'biochemistry', 'serum', [$numeric('URIC', 'Uric acid', '3084-1', 'mg/dL', 1, [['low' => 3, 'high' => 7] + $abim])]),
            $this->test('KFT', 'Kidney function test', 'biochemistry', 'serum', panel: true, components: ['UREA', 'CREAT', 'URIC'], price: 55000),
            $this->test('CHOL', 'Total cholesterol', 'biochemistry', 'serum', [$numeric('CHOL', 'Cholesterol', '2093-3', 'mg/dL', 0, [['high' => 200] + $abim])], notes: 'ABIM desirable cholesterol is <200 mg/dL, a treatment goal rather than a Gaussian reference interval.'),
            $this->test('TG', 'Triglycerides', 'biochemistry', 'serum', [$numeric('TG', 'Triglycerides', '2571-8', 'mg/dL', 0, [['high' => 150] + $abim])], notes: 'ABIM normal fasting triglycerides <150 mg/dL.'),
            $this->test('HDL', 'HDL cholesterol', 'biochemistry', 'serum', [$numeric('HDL', 'HDL cholesterol', '2085-9', 'mg/dL', 0, [])], notes: 'ABIM classifies low HDL as <40 mg/dL in men and <50 mg/dL in women. No two-sided interval is seeded.'),
            $this->test('LDL', 'LDL cholesterol', 'biochemistry', 'serum', [$numeric('LDL', 'LDL cholesterol (Friedewald)', '13457-7', 'mg/dL', 0, [['high' => 100] + $abim], null, 'friedewald')], notes: 'Calculated only when triglycerides are below 400 mg/dL. ABIM optimal LDL is <100 mg/dL.'),
            $this->test('LIPID', 'Lipid profile', 'biochemistry', 'serum', panel: true, components: ['CHOL', 'TG', 'HDL', 'LDL'], price: 65000),
            $this->test('NA', 'Sodium', 'biochemistry', 'serum', [$numeric('NA', 'Sodium', '2951-2', 'mmol/L', 0, [['low' => 136, 'high' => 145] + $abim], ['low' => 120, 'high' => 160])]),
            $this->test('K', 'Potassium', 'biochemistry', 'serum', [$numeric('K', 'Potassium', '2823-3', 'mmol/L', 1, [['low' => 3.5, 'high' => 5] + $abim], ['low' => 2.8, 'high' => 6.2])]),
            $this->test('CL', 'Chloride', 'biochemistry', 'serum', [$numeric('CL', 'Chloride', '2075-0', 'mmol/L', 0, [['low' => 98, 'high' => 106] + $abim])]),
            $this->test('HCO3', 'Bicarbonate', 'biochemistry', 'serum', [$numeric('HCO3', 'Bicarbonate', '1963-8', 'mmol/L', 0, [['low' => 23, 'high' => 28] + $abim])]),
            $this->test('ELECT', 'Electrolytes', 'biochemistry', 'serum', panel: true, components: ['NA', 'K', 'CL', 'HCO3'], price: 50000),
            $this->test('CA', 'Calcium', 'biochemistry', 'serum', [$numeric('CA', 'Calcium, total', '17861-6', 'mg/dL', 1, [['low' => 8.6, 'high' => 10.2] + $abim])], notes: 'Total calcium. Ionised calcium is a different test.'),
            $this->test('PHOS', 'Phosphorus', 'biochemistry', 'serum', [$numeric('PHOS', 'Phosphorus', '2777-1', 'mg/dL', 1, [['low' => 3, 'high' => 4.5] + $abim])]),
            $this->test('AMY', 'Amylase', 'biochemistry', 'serum', [$numeric('AMY', 'Amylase', '1798-8', 'U/L', 0, [['low' => 25, 'high' => 125] + $abim])]),
            $this->test('LIPASE', 'Lipase', 'biochemistry', 'serum', [$numeric('LIPASE', 'Lipase', '3040-3', 'U/L', 0, [['low' => 10, 'high' => 140] + $abim])]),
            $this->test('CRP', 'CRP', 'biochemistry', 'serum', [$numeric('CRP', 'C-reactive protein', '1988-5', 'mg/dL', 2, [['high' => 0.8] + $abim])], notes: 'Standard CRP. High-sensitivity CRP is a separate assay and is not this test.'),
            $this->test('IRON', 'Serum iron', 'biochemistry', 'serum', [$numeric('IRON', 'Iron', '2498-4', 'µg/dL', 0, [['low' => 50, 'high' => 150] + $abim])]),
            $this->test('TIBC', 'TIBC', 'biochemistry', 'serum', [$numeric('TIBC', 'Total iron-binding capacity', '2500-7', 'µg/dL', 0, [['low' => 250, 'high' => 310] + $abim])]),
            $this->test('TSAT', 'Transferrin saturation', 'biochemistry', 'serum', [$numeric('TSAT', 'Transferrin saturation', '2502-3', '%', 0, [['low' => 20, 'high' => 50] + $abim], null, 'transferrin-saturation')]),
            $this->test('FERR', 'Ferritin', 'biochemistry', 'serum', [$numeric('FERR', 'Ferritin', '2276-4', 'ng/mL', 0, [
                ['sex' => 'male', 'low' => 24, 'high' => 336] + $abim,
                ['sex' => 'female', 'low' => 24, 'high' => 307] + $abim,
            ])]),
            $this->test('IRONST', 'Iron studies', 'biochemistry', 'serum', panel: true, components: ['IRON', 'TIBC', 'TSAT', 'FERR'], price: 90000),
            $this->test('TSH', 'TSH', 'endocrinology', 'serum', [$numeric('TSH', 'TSH', '3016-3', 'mIU/L', 2, [['low' => 0.5, 'high' => 4] + $abim])], notes: 'Adult interval. Pregnancy and age-specific intervals are not seeded.'),
            $this->test('FT4', 'Free T4', 'endocrinology', 'serum', [$numeric('FT4', 'Free T4', '3024-7', 'ng/dL', 2, [['low' => 0.8, 'high' => 1.8] + $abim])]),
            $this->test('FT3', 'Free T3', 'endocrinology', 'serum', [$numeric('FT3', 'Free T3', '3051-0', 'pg/mL', 2, [['low' => 2.3, 'high' => 4.2] + $abim])]),
            $this->test('T3', 'Total T3', 'endocrinology', 'serum', [$numeric('T3', 'Total T3', '3053-6', 'ng/dL', 0, [['low' => 80, 'high' => 180] + $abim])]),
            $this->test('T4', 'Total T4', 'endocrinology', 'serum', [$numeric('T4', 'Total T4', '3026-2', 'µg/dL', 1, [['low' => 5, 'high' => 12] + $abim])]),
            $this->test('THYROID', 'Thyroid profile', 'endocrinology', 'serum', panel: true, components: ['TSH', 'FT4', 'FT3', 'T3', 'T4'], price: 85000),
            $this->test('VITD', 'Vitamin D (25-OH)', 'endocrinology', 'serum', [$numeric('VITD', '25-OH vitamin D', '1989-3', 'ng/mL', 1, [['low' => 30, 'high' => 60] + $abim])]),
            $this->test('B12', 'Vitamin B12', 'endocrinology', 'serum', [$numeric('B12', 'Vitamin B12', '2132-9', 'pg/mL', 0, [['low' => 200, 'high' => 800] + $abim])]),
            $this->test('CORT', 'Cortisol', 'endocrinology', 'serum', [$numeric('CORT', 'Cortisol, morning', '2143-6', 'µg/dL', 1, [['low' => 5, 'high' => 25] + $abim])], notes: 'ABIM 8 AM plasma cortisol is 5–25 µg/dL. Collection time is required.'),
            $this->qualitative('HIV', 'HIV 1 & 2 screening', 'serology', 'serum', 'HIV12', '29893-5'),
            $this->qualitative('HBSAG', 'HBsAg', 'serology', 'serum', 'HBSAG', '5196-1'),
            $this->qualitative('HCV', 'Anti-HCV', 'serology', 'serum', 'HCV', '16128-1'),
            $this->qualitative('NS1', 'Dengue NS1', 'serology', 'serum', 'NS1', '75377-7'),
            $this->qualitative('DENIGM', 'Dengue IgM', 'serology', 'serum', 'DENIGM', '29658-2'),
            $this->qualitative('DENIGG', 'Dengue IgG', 'serology', 'serum', 'DENIGG', '29661-6'),
            $this->test('DENGUE', 'Dengue NS1 / IgM / IgG', 'serology', 'serum', panel: true, components: ['NS1', 'DENIGM', 'DENIGG'], price: 110000, notes: 'Record the day of illness. Confirmatory pathways are outside this screening panel.'),
            $this->test('WIDAL', 'Widal', 'serology', 'serum', [[
                'code' => 'WIDAL', 'label' => 'Widal titres', 'type' => 'narrative',
            ]], notes: 'Report titres and the method limitation. This is not a standalone diagnosis of enteric fever.'),
            $this->test('RPR', 'RPR / VDRL', 'serology', 'serum', [[
                'code' => 'RPR', 'label' => 'RPR', 'type' => 'qualitative', 'options' => ['Non-reactive', 'Reactive'], 'loinc' => '20507-0',
            ]], notes: 'If reactive, record the titre in the comment.'),
            $this->test('UCOL', 'Urine colour', 'urinalysis', 'urine', [['code' => 'UCOL', 'label' => 'Colour', 'type' => 'text']]),
            $this->test('UAPP', 'Urine appearance', 'urinalysis', 'urine', [['code' => 'UAPP', 'label' => 'Appearance', 'type' => 'text']]),
            $this->test('UPH', 'Urine pH', 'urinalysis', 'urine', [$numeric('UPH', 'pH', '2756-5', null, 1, [['low' => 4.5, 'high' => 8] + $abim])]),
            $this->test('USG', 'Urine specific gravity', 'urinalysis', 'urine', [$numeric('USG', 'Specific gravity', '5811-5', null, 3, [['low' => 1.002, 'high' => 1.03] + $abim])]),
            $this->qualitative('UPRO', 'Urine protein', 'urinalysis', 'urine', 'UPRO', '5804-0', ['Negative', 'Trace', '1+', '2+', '3+']),
            $this->qualitative('UGLU', 'Urine glucose', 'urinalysis', 'urine', 'UGLU', '5792-7', ['Negative', 'Trace', '1+', '2+', '3+']),
            $this->test('UMIC', 'Urine microscopy', 'urinalysis', 'urine', [['code' => 'UMIC', 'label' => 'Microscopy', 'type' => 'narrative']]),
            $this->test('URINE', 'Urine routine and microscopy', 'urinalysis', 'urine', panel: true, components: ['UCOL', 'UAPP', 'UPH', 'USG', 'UPRO', 'UGLU', 'UMIC'], price: 25000),
            $this->test('STOOL', 'Stool routine and microscopy', 'microbiology', 'stool', [['code' => 'STOOL', 'label' => 'Stool findings', 'type' => 'narrative']]),
            $this->test('UCULT', 'Urine culture and sensitivity', 'microbiology', 'urine', [['code' => 'UCULT', 'label' => 'Culture result', 'type' => 'narrative']], notes: 'Record growth, colony count, organism and susceptibility.'),
            $this->test('BCULT', 'Blood culture', 'microbiology', 'culture', [['code' => 'BCULT', 'label' => 'Blood culture', 'type' => 'narrative']], notes: 'Record bottle type, incubation and organism.'),
            $this->test('SCULT', 'Sputum culture', 'microbiology', 'sputum', [['code' => 'SCULT', 'label' => 'Sputum culture', 'type' => 'narrative']], notes: 'Record specimen quality.'),
            $this->test('HISTO', 'Biopsy / histopathology', 'histopathology', 'tissue', [['code' => 'HISTO', 'label' => 'Histopathology', 'type' => 'narrative']], price: 150000, notes: 'Gross, microscopy and diagnosis.'),
            $this->test('FNAC', 'FNAC', 'histopathology', 'aspirate', [['code' => 'FNAC', 'label' => 'FNAC', 'type' => 'narrative']], price: 90000, notes: 'Record site, adequacy and conclusion.'),
            $this->test('ANA', 'ANA', 'immunology', 'serum', [[
                'code' => 'ANA', 'label' => 'ANA', 'type' => 'qualitative', 'options' => ['Negative', 'Positive'], 'loinc' => '8061-4',
            ]], notes: 'ABIM ANA screening threshold is commonly reported to ≤1:40. Record titre and pattern in the comment.'),
            $this->test('RF', 'Rheumatoid factor', 'immunology', 'serum', [$numeric('RF', 'Rheumatoid factor', '11572-5', 'IU/mL', 1, [['high' => 24] + $abim])], notes: 'ABIM nephelometric rheumatoid factor <24 IU/mL.'),
        ], $this->extendedTests($abim));
    }

    /**
     * @param  array{source: string, url: string}  $abim
     * @return list<array<string, mixed>>
     */
    private function extendedTests(array $abim): array
    {
        $who = ['source' => 'WHO laboratory manual for the examination and processing of human semen, 6th edition, 2021', 'url' => 'https://www.who.int/publications/i/item/9789240030787'];
        $num = function (string $code, string $label, ?string $loinc, ?string $unit, int $decimals, array $intervals, ?array $critical = null): array {
            return [
                'code' => $code,
                'label' => $label,
                'loinc' => $loinc,
                'unit' => $unit,
                'decimals' => $decimals,
                'intervals' => $intervals,
                'critical' => $critical,
                'formula' => null,
                'type' => 'numeric',
            ];
        };
        $text = function (string $code, string $label, string $type = 'narrative', ?array $options = null, ?string $loinc = null): array {
            return [
                'code' => $code,
                'label' => $label,
                'type' => $type,
                'options' => $options,
                'loinc' => $loinc,
            ];
        };

        return [
            $this->test('G6PD', 'G6PD', 'haematology', 'edta', [$num('G6PD', 'G6PD', null, 'U/g Hb', 1, [['low' => 5, 'high' => 15] + $abim])], notes: 'ABIM G6PD is 5–15 units/g of hemoglobin.'),
            $this->test('SICKLE', 'Sickling test', 'haematology', 'edta', [$text('SICKLE', 'Sickling', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('HBELEC', 'Haemoglobin electrophoresis', 'haematology', 'edta', [$text('HBELEC', 'Haemoglobin fractions')], method: 'Electrophoresis', notes: 'Record HbA, HbA2, HbF and any variant. Fractions are method specific.'),
            $this->test('MALARIA', 'Malaria smear', 'haematology', 'edta', [$text('MALARIA', 'Malaria smear', 'qualitative', ['Negative', 'P. falciparum', 'P. vivax', 'Mixed', 'Other'])], method: 'Microscopy'),
            $this->test('MARROW', 'Bone marrow examination', 'haematology', 'marrow', [$text('MARROW', 'Bone marrow')], price: 180000, notes: 'Record the site, adequacy, and the aspirate or trephine findings.'),
            $this->test('FIB', 'Fibrinogen', 'coagulation', 'citrate', [$num('FIB', 'Fibrinogen', '3255-7', 'mg/dL', 0, [['low' => 200, 'high' => 400] + $abim])]),
            $this->test('BT', 'Bleeding time', 'coagulation', 'bedside', [$num('BT', 'Bleeding time', null, 'minutes', 1, [['high' => 8] + $abim])], notes: 'ABIM template bleeding time is <8 minutes. This is a bedside procedure, not a tube test.'),
            $this->test('LDH', 'LDH', 'biochemistry', 'serum', [$num('LDH', 'Lactate dehydrogenase', '2532-0', 'U/L', 0, [['low' => 80, 'high' => 225] + $abim])]),
            $this->test('MG', 'Magnesium', 'biochemistry', 'serum', [$num('MG', 'Magnesium', '19123-9', 'mg/dL', 1, [['low' => 1.6, 'high' => 2.6] + $abim])]),
            $this->test('NH3', 'Ammonia', 'biochemistry', 'edta', [$num('NH3', 'Ammonia', null, 'µg/dL', 0, [['low' => 40, 'high' => 70] + $abim])], notes: 'ABIM plasma ammonia is 40–70 µg/dL. Transport on ice.'),
            $this->test('CK', 'Creatine kinase', 'biochemistry', 'serum', [$num('CK', 'Creatine kinase', '2157-6', 'U/L', 0, [
                ['sex' => 'male', 'low' => 55, 'high' => 170] + $abim,
                ['sex' => 'female', 'low' => 30, 'high' => 135] + $abim,
            ])]),
            $this->test('CKMB', 'CK-MB', 'biochemistry', 'serum', [$num('CKMB', 'CK-MB', '13969-1', '% of total CK', 1, [['high' => 5] + $abim])], notes: 'ABIM CK-MB isoenzymes are <5% of total CK.'),
            $this->test('TROP', 'Troponin I', 'biochemistry', 'serum', [$num('TROP', 'Troponin I', '10839-9', 'ng/mL', 2, [['high' => 0.04] + $abim])], notes: 'ABIM conventional troponin I is ≤0.04 ng/mL. High-sensitivity assays use a different cut-off and are a separate test.'),
            $this->test('HS_TROP', 'High-sensitivity troponin I', 'biochemistry', 'serum', [$num('HSTROP', 'High-sensitivity troponin I', null, 'ng/L', 1, [
                ['sex' => 'male', 'high' => 20] + $abim,
                ['sex' => 'female', 'high' => 15] + $abim,
            ])], notes: 'ABIM high-sensitivity troponin I is ≤15 ng/L in women and ≤20 ng/L in men.'),
            $this->test('BNP', 'BNP', 'biochemistry', 'edta', [$num('BNP', 'B-type natriuretic peptide', null, 'pg/mL', 0, [['high' => 100] + $abim])], notes: 'ABIM BNP is <100 pg/mL.'),
            $this->test('ACR', 'Urine albumin/creatinine ratio', 'biochemistry', 'urine', [$num('ACR', 'Albumin/creatinine ratio', '9318-7', 'mg/g', 1, [['high' => 30] + $abim])], notes: 'ABIM albumin-to-creatinine ratio is <30 mg/g.'),
            $this->test('EGFR', 'eGFR', 'biochemistry', 'serum', [$num('EGFR', 'eGFR', '98979-8', 'mL/min/1.73 m2', 0, [])], notes: 'Calculate with the laboratory-approved equation. No single reference interval is seeded.'),
            $this->test('SPEP', 'Serum protein electrophoresis', 'biochemistry', 'serum', [$text('SPEP', 'Protein electrophoresis')], method: 'Electrophoresis'),
            $this->test('FOLATE', 'Serum folate', 'biochemistry', 'serum', [$num('FOLATE', 'Folate', '2284-8', 'ng/mL', 1, [])], notes: 'Interval depends on the assay. Confirm the manufacturer range before reporting.'),
            $this->test('PTH', 'Parathyroid hormone', 'endocrinology', 'serum', [$num('PTH', 'Intact PTH', null, 'pg/mL', 0, [['low' => 10, 'high' => 65] + $abim])], notes: 'ABIM intact PTH is 10–65 pg/mL. The dialysis target is different and is not used here.'),
            $this->test('ACTH', 'ACTH', 'endocrinology', 'edta', [$num('ACTH', 'ACTH', null, 'pg/mL', 0, [['low' => 10, 'high' => 60] + $abim])], notes: 'ABIM morning ACTH is 10–60 pg/mL. Record the collection time.'),
            $this->test('PRL', 'Prolactin', 'endocrinology', 'serum', [$num('PRL', 'Prolactin', null, 'ng/mL', 1, [['high' => 20] + $abim])], notes: 'ABIM prolactin is <20 ng/mL.'),
            $this->test('TESTO', 'Testosterone', 'endocrinology', 'serum', [$num('TESTO', 'Total testosterone', '2986-8', 'ng/dL', 0, [
                ['sex' => 'male', 'low' => 291, 'high' => 1100] + $abim,
                ['sex' => 'female', 'low' => 18, 'high' => 54] + $abim,
            ])]),
            $this->test('FSH', 'FSH', 'endocrinology', 'serum', [$num('FSH', 'FSH', null, 'mIU/mL', 1, [])], notes: 'ABIM FSH values depend on sex and cycle phase. Record the phase; no single interval is seeded.'),
            $this->test('LH', 'LH', 'endocrinology', 'serum', [$num('LH', 'LH', null, 'mIU/mL', 1, [
                ['sex' => 'male', 'low' => 2, 'high' => 9] + $abim,
            ])], notes: 'ABIM adult male LH is 2–9 mIU/mL. Female values depend on cycle phase and are not seeded as one interval.'),
            $this->test('E2', 'Estradiol', 'endocrinology', 'serum', [$num('E2', 'Estradiol', null, 'pg/mL', 0, [])], notes: 'ABIM estradiol depends on cycle phase and menopause. Record the phase; no single interval is seeded.'),
            $this->test('PROG', 'Progesterone', 'endocrinology', 'serum', [$num('PROG', 'Progesterone', null, 'ng/mL', 2, [])], notes: 'ABIM progesterone depends on cycle phase. Record the phase.'),
            $this->test('INSULIN', 'Fasting insulin', 'endocrinology', 'serum', [$num('INSULIN', 'Insulin', '20448-7', 'µIU/mL', 1, [])], notes: 'Record fasting status. Confirm the assay interval locally.'),
            $this->test('HORMONE', 'Fertility hormone profile', 'endocrinology', 'serum', panel: true, components: ['FSH', 'LH', 'PRL', 'TESTO', 'E2'], price: 140000),
            $this->test('AFP', 'Alpha-fetoprotein', 'tumor_markers', 'serum', [$num('AFP', 'AFP', null, 'ng/mL', 1, [['high' => 10] + $abim])], notes: 'ABIM serum AFP is <10 ng/mL.'),
            $this->test('CEA', 'CEA', 'tumor_markers', 'serum', [$num('CEA', 'CEA', null, 'ng/mL', 1, [['high' => 2.5] + $abim])], notes: 'ABIM plasma CEA is <2.5 ng/mL.'),
            $this->test('CA125', 'CA 125', 'tumor_markers', 'serum', [$num('CA125', 'CA 125', null, 'U/mL', 1, [['high' => 35] + $abim])], notes: 'ABIM CA 125 is <35 U/mL.'),
            $this->test('CA199', 'CA 19-9', 'tumor_markers', 'serum', [$num('CA199', 'CA 19-9', null, 'U/mL', 1, [['high' => 37] + $abim])], notes: 'ABIM CA 19-9 is 0–37 U/mL.'),
            $this->test('PSA', 'PSA', 'tumor_markers', 'serum', [$num('PSA', 'PSA', '2857-1', 'ng/mL', 2, [])], notes: 'ABIM states there is no specific normal or abnormal PSA level. Do not apply a universal cut-off.'),
            $this->test('BHCG', 'Beta-hCG', 'tumor_markers', 'serum', [$num('BHCG', 'Beta-hCG', null, 'U/L', 1, [
                ['sex' => 'male', 'high' => 1.4] + $abim,
            ])], notes: 'ABIM male beta-hCG is <1.4 U/L. Pregnancy and postmenopausal values are different, so no female interval is seeded.'),
            $this->test('ABO', 'ABO group', 'blood_bank', 'edta', [$text('ABO', 'ABO group', 'qualitative', ['A', 'B', 'AB', 'O'], '883-9')]),
            $this->test('RH', 'Rh D', 'blood_bank', 'edta', [$text('RH', 'Rh D', 'qualitative', ['Positive', 'Negative'])]),
            $this->test('ABSCR', 'Antibody screen', 'blood_bank', 'edta', [$text('ABSCR', 'Antibody screen', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('DCT', 'Direct Coombs test', 'blood_bank', 'edta', [$text('DCT', 'Direct antiglobulin test', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('ICT', 'Indirect Coombs test', 'blood_bank', 'serum', [$text('ICT', 'Indirect antiglobulin test', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('XMATCH', 'Crossmatch', 'blood_bank', 'edta', [$text('XMATCH', 'Crossmatch', 'qualitative', ['Compatible', 'Incompatible'])], notes: 'Record the donor unit number in the comment. This is not a substitute for the blood-bank worksheet.'),
            $this->test('BLOODGROUP', 'Blood group and Rh', 'blood_bank', 'edta', panel: true, components: ['ABO', 'RH'], price: 15000),
            $this->qualitative('MPAG', 'Malaria antigen', 'serology', 'edta', 'MPAG', null, ['Negative', 'P. falciparum', 'P. vivax', 'Mixed']),
            $this->qualitative('COVIDAG', 'SARS-CoV-2 antigen', 'serology', 'swab', 'COVIDAG', '94558-4'),
            $this->qualitative('HAVIGM', 'Hepatitis A IgM', 'serology', 'serum', 'HAVIGM'),
            $this->qualitative('HEVIGM', 'Hepatitis E IgM', 'serology', 'serum', 'HEVIGM'),
            $this->qualitative('LEPTO', 'Leptospira IgM', 'serology', 'serum', 'LEPTO'),
            $this->qualitative('CHIKV', 'Chikungunya IgM', 'serology', 'serum', 'CHIKV'),
            $this->qualitative('SCRUB', 'Scrub typhus IgM', 'serology', 'serum', 'SCRUB'),
            $this->qualitative('CMVIGM', 'CMV IgM', 'serology', 'serum', 'CMVIGM'),
            $this->qualitative('RUBG', 'Rubella IgG', 'serology', 'serum', 'RUBG'),
            $this->test('ASO', 'ASO titre', 'serology', 'serum', [$num('ASO', 'ASO titre', null, 'Todd units', 0, [['high' => 200] + $abim])], notes: 'ABIM antistreptolysin O titre is <200 Todd units.'),
            $this->test('HPYLORI', 'H. pylori stool antigen', 'serology', 'stool', [$text('HPYLORI', 'H. pylori antigen', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('UPT', 'Urine pregnancy test', 'urinalysis', 'urine', [$text('UPT', 'hCG', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('FOBT', 'Fecal occult blood', 'clinical_pathology', 'stool', [$text('FOBT', 'Occult blood', 'qualitative', ['Negative', 'Positive'])]),
            $this->test('DSDNA', 'Anti-dsDNA', 'immunology', 'serum', [$num('DSDNA', 'Anti-dsDNA', null, 'IU/mL', 1, [['low' => 0, 'high' => 7] + $abim])], notes: 'ABIM antibodies to double-stranded DNA are 0–7 IU/mL.'),
            $this->test('CCP', 'Anti-CCP', 'immunology', 'serum', [$num('CCP', 'Anti-CCP', null, 'units', 1, [['high' => 20] + $abim])], notes: 'ABIM anti-cyclic citrullinated peptide antibodies are <20 units.'),
            $this->test('C3', 'Complement C3', 'immunology', 'serum', [$num('C3', 'C3', null, 'mg/dL', 0, [['low' => 100, 'high' => 233] + $abim])]),
            $this->test('C4', 'Complement C4', 'immunology', 'serum', [$num('C4', 'C4', null, 'mg/dL', 0, [['low' => 14, 'high' => 48] + $abim])]),
            $this->test('IGE', 'Total IgE', 'immunology', 'serum', [$num('IGE', 'IgE', null, 'IU/mL', 0, [['high' => 380] + $abim])], notes: 'ABIM IgE is <380 IU/mL.'),
            $this->test('IGG', 'IgG', 'immunology', 'serum', [$num('IGG', 'IgG', null, 'mg/dL', 0, [['low' => 800, 'high' => 1500] + $abim])]),
            $this->test('IGA', 'IgA', 'immunology', 'serum', [$num('IGA', 'IgA', null, 'mg/dL', 0, [['low' => 90, 'high' => 325] + $abim])]),
            $this->test('IGM', 'IgM', 'immunology', 'serum', [$num('IGM', 'IgM', null, 'mg/dL', 0, [['low' => 45, 'high' => 150] + $abim])]),
            $this->test('ANCA', 'ANCA', 'immunology', 'serum', [$text('ANCA', 'ANCA', 'qualitative', ['Negative', 'C-ANCA', 'P-ANCA'])], notes: 'Record the titre and the antigen (PR3 or MPO) in the comment.'),
            $this->test('ATPO', 'Anti-TPO', 'immunology', 'serum', [$num('ATPO', 'Anti-TPO', null, 'U/mL', 1, [['high' => 2] + $abim])], notes: 'ABIM antithyroid peroxidase antibodies are <2.0 U/mL. The cut-off is assay specific.'),
            $this->test('PCULT', 'Pus culture and sensitivity', 'microbiology', 'swab', [$text('PCULT', 'Pus culture')], notes: 'Record the site, growth, organism and susceptibility.'),
            $this->test('STCULT', 'Stool culture', 'microbiology', 'stool', [$text('STCULT', 'Stool culture')]),
            $this->test('AFB', 'AFB smear', 'microbiology', 'sputum', [$text('AFB', 'AFB smear', 'qualitative', ['Negative', 'Scanty', '1+', '2+', '3+'])], method: 'Ziehl-Neelsen'),
            $this->test('KOH', 'KOH mount', 'microbiology', 'swab', [$text('KOH', 'KOH mount', 'qualitative', ['Negative', 'Fungal elements seen'])]),
            $this->test('GRAM', 'Gram stain', 'microbiology', 'swab', [$text('GRAM', 'Gram stain')], method: 'Gram stain'),
            $this->test('FCULT', 'Fungal culture', 'microbiology', 'swab', [$text('FCULT', 'Fungal culture')]),
            $this->test('COVIDPCR', 'SARS-CoV-2 PCR', 'molecular', 'swab', [$text('COVIDPCR', 'SARS-CoV-2 RNA', 'qualitative', ['Not detected', 'Detected'])], method: 'Nucleic acid amplification'),
            $this->test('MTBPCR', 'MTB PCR', 'molecular', 'sputum', [$text('MTBPCR', 'M. tuberculosis', 'qualitative', ['Not detected', 'Detected'])], method: 'Nucleic acid amplification', notes: 'Record rifampicin resistance when the assay reports it.'),
            $this->test('HBVDNA', 'HBV DNA', 'molecular', 'serum', [$num('HBVDNA', 'HBV DNA', null, 'IU/mL', 0, [])], notes: 'Quantitative viral load. The reportable range is assay specific.'),
            $this->test('HCVRNA', 'HCV RNA', 'molecular', 'serum', [$num('HCVRNA', 'HCV RNA', null, 'IU/mL', 0, [])], notes: 'Quantitative viral load. The reportable range is assay specific.'),
            $this->test('HIVVL', 'HIV viral load', 'molecular', 'edta', [$num('HIVVL', 'HIV-1 RNA', null, 'copies/mL', 0, [])], notes: 'Quantitative viral load. The reportable range is assay specific.'),
            $this->test('HPV', 'HPV DNA', 'molecular', 'swab', [$text('HPV', 'HPV DNA', 'qualitative', ['Not detected', 'Detected'])], notes: 'Record the genotype when the assay identifies one.'),
            $this->test('HLA', 'HLA typing', 'molecular', 'edta', [$text('HLA', 'HLA type')], notes: 'Record the locus and the resolution of the typing.'),
            $this->test('PAP', 'Pap smear', 'cytology', 'aspirate', [$text('PAP', 'Pap smear')], price: 80000, notes: 'Record adequacy and the reporting system used, such as Bethesda.'),
            $this->test('FLUIDCYT', 'Fluid cytology', 'cytology', 'fluid', [$text('FLUIDCYT', 'Fluid cytology')], price: 90000, notes: 'Record the fluid site and adequacy.'),
            $this->test('SPUTCYT', 'Sputum cytology', 'cytology', 'sputum', [$text('SPUTCYT', 'Sputum cytology')]),
            $this->test('SEMVOL', 'Semen volume', 'clinical_pathology', 'semen', [$num('SEMVOL', 'Volume', null, 'mL', 1, [['low' => 1.4] + $who])]),
            $this->test('SEMCONC', 'Sperm concentration', 'clinical_pathology', 'semen', [$num('SEMCONC', 'Sperm concentration', null, 'million/mL', 1, [['low' => 16] + $who])]),
            $this->test('SEMMOT', 'Sperm progressive motility', 'clinical_pathology', 'semen', [$num('SEMMOT', 'Progressive motility', null, '%', 0, [['low' => 30] + $who])]),
            $this->test('SEMMORPH', 'Sperm morphology', 'clinical_pathology', 'semen', [$num('SEMMORPH', 'Normal forms', null, '%', 0, [['low' => 4] + $who])]),
            $this->test('SEMEN', 'Semen analysis', 'clinical_pathology', 'semen', panel: true, components: ['SEMVOL', 'SEMCONC', 'SEMMOT', 'SEMMORPH'], price: 60000, notes: 'WHO 2021 lower reference limits are decision limits for a fertility workup, not a diagnosis.'),
            $this->test('CSFWBC', 'CSF cell count', 'clinical_pathology', 'csf', [$num('CSFWBC', 'CSF leukocytes', null, 'cells/µL', 0, [['low' => 0, 'high' => 5] + $abim])]),
            $this->test('CSFPRO', 'CSF protein', 'clinical_pathology', 'csf', [$num('CSFPRO', 'CSF protein', null, 'mg/dL', 0, [['low' => 15, 'high' => 45] + $abim])]),
            $this->test('CSFGLU', 'CSF glucose', 'clinical_pathology', 'csf', [$num('CSFGLU', 'CSF glucose', null, 'mg/dL', 0, [['low' => 50, 'high' => 75] + $abim])]),
            $this->test('CSF', 'CSF analysis', 'clinical_pathology', 'csf', panel: true, components: ['CSFWBC', 'CSFPRO', 'CSFGLU'], price: 70000),
            $this->test('PLEURAL', 'Pleural fluid analysis', 'clinical_pathology', 'fluid', [$text('PLEURAL', 'Pleural fluid')], notes: 'Record appearance, cell count, protein, glucose and any organisms.'),
            $this->test('ASCITES', 'Ascitic fluid analysis', 'clinical_pathology', 'fluid', [$text('ASCITES', 'Ascitic fluid')], notes: 'Record appearance, cell count, protein and albumin when measured.'),
            $this->test('SYNOVIAL', 'Synovial fluid analysis', 'clinical_pathology', 'fluid', [$text('SYNOVIAL', 'Synovial fluid')], notes: 'Record appearance, cell count and crystals.'),
            $this->test('STONE', 'Urinary stone analysis', 'clinical_pathology', 'urine', [$text('STONE', 'Stone composition')], notes: 'Record the method and each constituent.'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @param  list<string>  $components
     * @return array<string, mixed>
     */
    private function test(
        string $code,
        string $name,
        string $department,
        string $specimen,
        array $results = [],
        bool $panel = false,
        array $components = [],
        int $price = 20000,
        ?string $loinc = null,
        ?string $method = null,
        ?string $notes = null,
        int $tat = 240,
    ): array {
        return compact('code', 'name', 'department', 'specimen', 'results', 'panel', 'components', 'price', 'loinc', 'method', 'notes', 'tat');
    }

    /**
     * @param  list<string>  $options
     * @return array<string, mixed>
     */
    private function qualitative(string $code, string $name, string $department, string $specimen, string $resultCode, ?string $loinc = null, array $options = ['Non-reactive', 'Reactive']): array
    {
        return $this->test($code, $name, $department, $specimen, [[
            'code' => $resultCode,
            'label' => $name,
            'type' => 'qualitative',
            'options' => $options,
            'loinc' => $loinc,
        ]], notes: 'Screening result. Confirmatory testing and authorised wording are required before a clinical conclusion.');
    }
}
