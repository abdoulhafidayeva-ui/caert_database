<?php

namespace App\Form;

use App\Service\Config\EditableEnvDefinition;
use App\Service\Config\EditableEnvRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class EnvConfigType extends AbstractType
{
    public function __construct(private readonly EditableEnvRegistry $registry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->registry->all() as $definition) {
            $builder->add($definition->key, $this->resolveFieldType($definition), $this->resolveFieldOptions($definition));
        }

        $builder->add('save', SubmitType::class, [
            'label' => 'common.save',
            'attr' => ['class' => 'btn btn-primary'],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            foreach ($this->registry->all() as $key => $definition) {
                if ($definition->type !== 'password') {
                    continue;
                }

                $data[$key] = '';
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'messages',
        ]);
    }

    private function resolveFieldType(EditableEnvDefinition $definition): string
    {
        return match ($definition->type) {
            'password' => PasswordType::class,
            'url' => UrlType::class,
            'choice' => ChoiceType::class,
            default => TextType::class,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFieldOptions(EditableEnvDefinition $definition): array
    {
        $options = [
            'label' => $definition->label,
            'required' => false,
            'help' => $definition->help,
            'attr' => [
                'autocomplete' => 'off',
            ],
        ];

        if ($definition->type === 'password') {
            $options['always_empty'] = true;
            $options['attr']['placeholder'] = 'env_config.placeholder.keep_current';
            $options['constraints'] = $this->passwordConstraints($definition);
        } elseif ($definition->type === 'choice') {
            $options['choices'] = $definition->choices;
            $options['placeholder'] = false;
        } elseif ($definition->required && $definition->type !== 'password') {
            $options['constraints'][] = new NotBlank(message: 'env_config.error.required');
        }

        if ($definition->key === 'MAILER_DSN') {
            $options['constraints'] = array_merge($options['constraints'] ?? [], [
                new Regex(
                    pattern: '/^(null:\/\/null|smtp(s)?:\/\/.+|sendmail:\/\/default)$/',
                    message: 'env_config.error.mailer_dsn',
                ),
            ]);
        }

        if ($definition->key === 'DATABASE_URL') {
            $options['constraints'] = array_merge($options['constraints'] ?? [], [
                new Regex(
                    pattern: '/^(mysql|postgresql|sqlite):\/\/.+/',
                    message: 'env_config.error.database_url',
                ),
            ]);
        }

        if ($definition->key === 'APP_SECRET') {
            $options['constraints'] = array_merge($options['constraints'] ?? [], [
                new Length(min: 16, minMessage: 'env_config.error.app_secret_length'),
            ]);
        }

        return $options;
    }

    /**
     * @return list<object>
     */
    private function passwordConstraints(EditableEnvDefinition $definition): array
    {
        if (!$definition->required) {
            return [
                new Length(min: 16, minMessage: 'env_config.error.app_secret_length'),
            ];
        }

        return [];
    }
}
