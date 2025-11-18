<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Support\Validator;

class ValidatorTest extends TestCase
{
    public function testStringRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'string'];
        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testDotNotationValidationPasses(): void
    {
        $rules = [
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ];

        $data = [
            'search' => [
                'term' => 'example',
                'current_page' => 1,
            ]
        ];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMinRulePassesWithInteger(): void
    {
        $rules = ['current_page' => 'min:1'];
        $data = ['current_page' => 1];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMinRuleFailsWithInteger(): void
    {
        $rules = ['current_page' => 'min:5'];
        $data = ['current_page' => 2];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testMaxRulePassesWithInteger(): void
    {
        $rules = ['current_page' => 'max:10'];
        $data = ['current_page' => 5];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMaxRuleFailsWithInteger(): void
    {
        $rules = ['current_page' => 'max:5'];
        $data = ['current_page' => 10];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testStringRuleFails(): void
    {
        $data = ['name' => 123];
        $rules = ['name' => 'string'];
        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testRequiredWithPasses(): void
    {
        $data = ['email' => 'test@example.com', 'phone_number' => '123456789'];
        $rules = ['email' => 'required_with:phone_number'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }

    public function testRequiredWithFails(): void
    {
        $data = ['email' => '', 'phone_number' => '123456789'];
        $rules = ['email' => 'required_with:phone_number'];
        $validator = new Validator($data, $rules);

        $this->assertFalse($validator->validate());
    }

    public function testNullable(): void
    {
        $data = ['middle_name' => ''];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }

    public function testNullableStringPasses(): void
    {
        $data = ['middle_name' => null];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }

    public function testNullableStringFailsNonString(): void
    {
        $data = ['middle_name' => 123];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertFalse($validator->validate());
    }

    public function testIntegerPasses(): void
    {
        $rules = ['current_page' => 'integer'];
        $data = ['current_page' => 5];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testNullableStringPassesWithString(): void
    {
        $data = ['middle_name' => 'Anders'];
        $rules = ['middle_name' => 'nullable|string'];
        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate());
    }



    public function testRequiredRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testRequiredRuleFails(): void
    {
        $data = [];
        $rules = ['name' => 'required'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate(), 'Validering ska misslyckas eftersom `name` krävs, men är tomt.');
    }

    public function testSingleRuleAsString(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate(), 'Validering med en enkel regel som sträng ska passera.');
    }

    // Email: Passar och misslyckas
    public function testEmailRulePasses(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testEmailRuleFails(): void
    {
        $data = ['email' => 'fel-format'];
        $rules = ['email' => 'email'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Min och max längd
    public function testMinRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'min:3'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testMinRuleFails(): void
    {
        $data = ['name' => 'Jo'];
        $rules = ['name' => 'min:3'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    public function testMaxRulePasses(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'max:10'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testNullableWithConfirmed(): void
    {
        $data = [
            'password' => '', // Tomt värde, ska ignoreras av nullable
            'password_confirmation' => '',
        ];
        $rules = [
            'password' => 'nullable|confirmed:password_confirmation',
        ];

        $validator = new Validator($data, $rules);

        $this->assertTrue($validator->validate(), 'Password ska vara nullable och confirmed ska inte trigga fel');
    }

    public function testPasswordNullableAndConfirmed(): void
    {
        $data = [
            'password' => 'secret123', // Fyllt värde
            'password_confirmation' => 'secret123',
        ];
        $rules = [
            'password' => 'nullable|confirmed',
        ];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate(), 'Validering ska passera för password + confirmed.');
    }

public function testPasswordNullableConfirmed(): void
{
    $data = [
        'password' => null,
        'password_confirmation' => null,
    ];

    $rules = [
        'password_confirmation' => 'nullable|required_with:password|confirmed:password',
    ];

    $validator = new Validator($data, $rules);
    $this->assertTrue($validator->validate(), 'Validering ska passera eftersom fields är nullable.');
}

public function testPasswordConfirmedFails(): void
{
    $data = [
        'password' => 'secret123',
        'password_confirmation' => 'wrong',
    ];

    $rules = [
        'password_confirmation' => 'nullable|required_with:password|confirmed:password',
    ];

    $validator = new Validator($data, $rules);
    $this->assertFalse($validator->validate(), 'Valideringen ska misslyckas eftersom fält ej matchar.');
}

public function testPasswordConfirmedPasses(): void
{
    $data = [
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    $rules = [
        'password_confirmation' => 'nullable|required_with:password|confirmed:password',
    ];

    $validator = new Validator($data, $rules);
    $this->assertTrue($validator->validate(), 'Valideringen ska passera då fälten matchar.');
}

    public function testMaxRuleFails(): void
    {
        $data = ['name' => 'Jonathan'];
        $rules = ['name' => 'max:5'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Numeric validering
    public function testNumericRulePasses(): void
    {
        $data = ['price' => 123];
        $rules = ['price' => 'numeric'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testNumericRuleFails(): void
    {
        $data = ['price' => 'abc'];
        $rules = ['price' => 'numeric'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Alphanumeric validering
    public function testAlphanumericRulePasses(): void
    {
        $data = ['username' => 'JohnDoe123'];
        $rules = ['username' => 'alphanumeric'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testAlphanumericRuleFails(): void
    {
        $data = ['username' => 'John.Doe!'];
        $rules = ['username' => 'alphanumeric'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Regex
    public function testRegexRulePasses(): void
    {
        $data = ['username' => 'John123'];
        $rules = ['username' => 'regex:/^[a-zA-Z0-9]+$/'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testRegexRuleFails(): void
    {
        $data = ['username' => 'John_Doe'];
        $rules = ['username' => 'regex:/^[a-zA-Z0-9]+$/'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // In och Not in
    public function testInRulePasses(): void
    {
        $data = ['status' => 'active'];
        $rules = ['status' => 'in:active,inactive'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testNotInRulePasses(): void
    {
        $data = ['role' => 'viewer'];
        $rules = ['role' => 'not_in:admin,superuser'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    // IP validering
    public function testIpRulePasses(): void
    {
        $data = ['server' => '192.168.1.1'];
        $rules = ['server' => 'ip'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testIpRuleFails(): void
    {
        $data = ['server' => 'not-an-ip'];
        $rules = ['server' => 'ip'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Boolean
    public function testBooleanRulePasses(): void
    {
        $data = ['isActive' => true];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testBooleanRuleFails(): void
    {
        $data = ['isActive' => 'yes'];
        $rules = ['isActive' => 'boolean'];

        $validator = new Validator($data, $rules);
        $this->assertFalse($validator->validate());
    }

    // Date och DateFormat
    public function testDateRulePasses(): void
    {
        $data = ['birthday' => '2023-07-31'];
        $rules = ['birthday' => 'date'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testDateFormatRulePasses(): void
    {
        $data = ['start_date' => '31-07-2023'];
        $rules = ['start_date' => 'date_format:d-m-Y'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    // StartsWith och EndsWith
    public function testStartsWithRulePasses(): void
    {
        $data = ['username' => 'admin_user'];
        $rules = ['username' => 'starts_with:admin'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testEndsWithRulePasses(): void
    {
        $data = ['file' => 'report.pdf'];
        $rules = ['file' => 'ends_with:.pdf'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->validate());
    }
}