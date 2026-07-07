<?php

namespace App\Form;

use App\Entity\Candidat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class CandidatType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add("nom", TextType::class, [
                "attr" => ["class" => "form-control"],
                "constraints" => [
                    new NotBlank(["message" => "Le nom est obligatoire."]),
                ],
            ])
            ->add("prenoms", TextType::class, [
                "attr" => ["class" => "form-control"],
                "constraints" => [
                    new NotBlank(["message" => "Le prénom est obligatoire."]),
                ],
            ])
            ->add("email", EmailType::class, [
                "attr" => ["class" => "form-control"],
                "constraints" => [
                    new NotBlank([
                        "message" => "L'adresse email est obligatoire.",
                    ]),
                ],
            ])
            ->add("telephone", TextType::class, [
                "attr" => ["class" => "form-control"],
                "constraints" => [
                    new NotBlank([
                        "message" => "Le numéro de téléphone est obligatoire.",
                    ]),
                ],
            ])
            ->add("niveauEtude", ChoiceType::class, [
                "choices" => [
                    "Master" => "Master",
                    "Doctorat" => "Doctorat",
                    "Enseignant-Chercheur" => "Enseignant-Chercheur",
                    "Magistrat / Avocat" => "Professionnel de la justice",
                ],
                "attr" => [
                    "class" => "form-select",
                ],
            ])

            // --- VALIDATION DES FICHIERS ---
            ->add("contributionFile", FileType::class, [
                "label" =>
                    "Contribution scientifique (Word ou PDF, 25-35 pages)",
                "mapped" => false,
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "accept" =>
                        ".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                ],
                "constraints" => [
                    new NotBlank([
                        "message" => "Veuillez joindre votre contribution.",
                    ]),
                    new File([
                        "maxSize" => "20M",
                        "mimeTypes" => [
                            "application/pdf",
                            "application/msword",
                            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        ],
                        "mimeTypesMessage" =>
                            "Seuls les formats PDF, DOC et DOCX sont autorisés.",
                        "maxSizeMessage" =>
                            "Le fichier ne doit pas dépasser 20 Mo.",
                    ]),
                ],
            ])
            ->add("resumeFile", FileType::class, [
                "label" => "Résumé de la contribution (1 page max)",
                "mapped" => false,
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "accept" => ".pdf,application/pdf",
                ],
                "constraints" => [
                    new NotBlank(["message" => "Veuillez joindre le résumé."]),
                    new File([
                        "maxSize" => "5M",
                        "mimeTypes" => ["application/pdf"],
                        "mimeTypesMessage" =>
                            "Le résumé doit être au format PDF.",
                        "maxSizeMessage" =>
                            "Le fichier ne doit pas dépasser 5 Mo.",
                    ]),
                ],
            ])
            ->add("cvFile", FileType::class, [
                "label" => "Curriculum Vitae",
                "mapped" => false,
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "accept" => ".pdf,application/pdf",
                ],
                "constraints" => [
                    new NotBlank(["message" => "Veuillez joindre votre CV."]),
                    new File([
                        "maxSize" => "5M",
                        "mimeTypes" => ["application/pdf"],
                        "mimeTypesMessage" => "Le CV doit être au format PDF.",
                        "maxSizeMessage" =>
                            "Le fichier ne doit pas dépasser 5 Mo.",
                    ]),
                ],
            ])
            ->add("identityFile", FileType::class, [
                "label" => "Pièce d'identité ou Acte de naissance sécurisé",
                "mapped" => false,
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "accept" =>
                        ".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png",
                ],
                "constraints" => [
                    new NotBlank([
                        "message" => "Veuillez joindre votre pièce d'identité.",
                    ]),
                    new File([
                        "maxSize" => "5M",
                        "mimeTypes" => [
                            "application/pdf",
                            "image/jpeg",
                            "image/png",
                        ],
                        "mimeTypesMessage" =>
                            "Formats autorisés : PDF, JPG, PNG.",
                        "maxSizeMessage" =>
                            "Le fichier ne doit pas dépasser 5 Mo.",
                    ]),
                ],
            ])
            ->add("diplomaFile", FileType::class, [
                "label" => "Copie légalisée du diplôme",
                "mapped" => false,
                "required" => true,
                "attr" => [
                    "class" => "form-control",
                    "accept" => ".pdf,application/pdf",
                ],
                "constraints" => [
                    new NotBlank([
                        "message" =>
                            "Veuillez joindre la copie de votre diplôme.",
                    ]),
                    new File([
                        "maxSize" => "10M",
                        "mimeTypes" => ["application/pdf"],
                        "mimeTypesMessage" =>
                            "Le diplôme doit être au format PDF.",
                        "maxSizeMessage" =>
                            "Le fichier ne doit pas dépasser 10 Mo.",
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Candidat::class,
        ]);
    }
}
