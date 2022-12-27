<?php

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use function Pest\Faker\faker;
use WendellAdriel\ValidatedDTO\Exceptions\InvalidJsonException;
use WendellAdriel\ValidatedDTO\Tests\Datasets\ValidatedDTOInstance;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

beforeEach(function () {
    $this->subject_name = faker()->name;
});

it('instantiates a ValidatedDTO validating its data', function () {
    $validatedDTO = new ValidatedDTOInstance(['name' => $this->subject_name]);

    expect($validatedDTO)->toBeInstanceOf(ValidatedDTO::class)
        ->and($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('throws exception when trying to instantiate a ValidatedDTO with invalid data', function () {
    new ValidatedDTOInstance([]);
})->throws(ValidationException::class);

it('validates that is possible to set a property in a ValidatedDTO', function () {
    $validatedDTO = new ValidatedDTOInstance(['name' => $this->subject_name]);

    $validatedDTO->age = 30;

    expect($validatedDTO->age)->toBe(30);
});

it('returns null when trying to access a property that does not exist', function () {
    $validatedDTO = new ValidatedDTOInstance(['name' => $this->subject_name]);

    expect($validatedDTO->age)->toBeNull();
});

it('validates that a ValidatedDTO can be instantiated from a JSON string', function () {
    $validatedDTO = ValidatedDTOInstance::fromJson('{"name": "'.$this->subject_name.'"}');

    expect($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('throws exception when trying to instantiate a ValidatedDTO from an invalid JSON string', function () {
    ValidatedDTOInstance::fromJson('{"name": "'.$this->subject_name.'"');
})->throws(InvalidJsonException::class);

it('validates that a ValidatedDTO can be instantiated from a Request', function () {
    $request = new Request(['name' => $this->subject_name]);

    $validatedDTO = ValidatedDTOInstance::fromRequest($request);

    expect($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('validates that a ValidatedDTO can be instantiated from an Eloquent Model', function () {
    $model = new class() extends Model
    {
        protected $fillable = ['name'];
    };

    $model->fill(['name' => $this->subject_name]);

    $validatedDTO = ValidatedDTOInstance::fromModel($model);

    expect($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('validates that a ValidatedDTO can be instantiated from Command arguments', function () {
    $command = new class() extends Command
    {
        protected $signature
            = 'test:command
            {name : The name of the user}';

        public function __invoke()
        {
        }
    };

    Application::starting(function ($artisan) use ($command) {
        $artisan->add($command);
    });

    $this->artisan('test:command', ['name' => $this->subject_name]);

    $validatedDTO = ValidatedDTOInstance::fromCommandArguments($command);

    expect($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('validates that a ValidatedDTO can be instantiated from Command options', function () {
    $command = new class() extends Command
    {
        protected $signature
            = 'test:command
            {--name= : The name of the user}';

        public function __invoke()
        {
        }
    };

    Application::starting(function ($artisan) use ($command) {
        $artisan->add($command);
    });

    $this->artisan('test:command', ['--name' => $this->subject_name]);

    $validatedDTO = ValidatedDTOInstance::fromCommandOptions($command);

    expect($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('validates that a ValidatedDTO can be instantiated from a Command', function () {
    $command = new class() extends Command
    {
        protected $signature
            = 'test:command
            {name : The name of the user}
            {--age= : The age of the user}';

        public function __invoke()
        {
        }
    };

    Application::starting(function ($artisan) use ($command) {
        $artisan->add($command);
    });

    $this->artisan(
        'test:command',
        ['name' => $this->subject_name, '--age' => 30]
    );

    $validatedDTO = ValidatedDTOInstance::fromCommand($command);

    expect($validatedDTO->validatedData)
        ->toEqual(['name' => $this->subject_name, 'age' => 30])
        ->and($validatedDTO->validator->passes())
        ->toBeTrue();
});

it('validates that the ValidatedDTO can be converted into an array', function () {
    $validatedDTO = new ValidatedDTOInstance(['name' => $this->subject_name]);
    $result = $validatedDTO->toArray();

    expect($result)
        ->toBeArray()
        ->toEqual(['name' => $this->subject_name]);
});

it('validates that the ValidatedDTO can be converted into a JSON string', function () {
    $validatedDTO = new ValidatedDTOInstance(['name' => $this->subject_name]);
    $result = $validatedDTO->toJson();

    expect($result)
        ->toBeString()
        ->toBe('{"name":"'.$this->subject_name.'"}');
});

it('validates that the ValidatedDTO can be converted into an Eloquent Model', function () {
    $validatedDTO = new ValidatedDTOInstance(['name' => $this->subject_name]);

    $model = new class() extends Model
    {
        protected $fillable = ['name'];
    };

    $model_instance = $validatedDTO->toModel($model::class);

    expect($model_instance)->toBeInstanceOf(Model::class);
    $result = $model_instance->toArray();
    expect($result)
        ->toBeArray()
        ->toEqual(['name' => $this->subject_name]);
});
