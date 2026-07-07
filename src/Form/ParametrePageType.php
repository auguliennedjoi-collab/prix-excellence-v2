<?php

namespace App\Form;

use App\Entity\ParametrePage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParametrePageType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            // ===== PLAGES DE DATES DE L'ÉTUDE =====
            ->add("dateDebutEtude", DateType::class, [
                "label" => "Début de l'étude des dossiers",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])
            ->add("dateFinEtude", DateType::class, [
                "label" => "Fin de l'étude des dossiers",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])

            // ===== PLAGES DE DATES DE LA PRÉSÉLECTION =====
            ->add("dateDebutPreselection", DateType::class, [
                "label" => "Début de la présélection",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])
            ->add("dateFinPreselection", DateType::class, [
                "label" => "Fin de la présélection",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])

            // ===== PLAGES DE DATES DES AUDITIONS =====
            ->add("dateDebutAudition", DateType::class, [
                "label" => "Début des auditions",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])
            ->add("dateFinAudition", DateType::class, [
                "label" => "Fin des auditions",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])

            // ===== PLAGES DE DATES DE LA PROCLAMATION =====
            ->add("dateDebutProclamation", DateType::class, [
                "label" => "Début de la proclamation",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])
            ->add("dateFinProclamation", DateType::class, [
                "label" => "Fin de la proclamation",
                "widget" => "single_text",
                "attr" => ["class" => "form-control"],
            ])

            // ===== INFOS TEXTUELLES ET CONTENUS =====
            ->add("quiPeutParticiper", TextareaType::class, [
                "label" => "Qui peut participer ?",
                "attr" => ["class" => "form-control", "rows" => 4],
                "help" =>
                    "Texte libre, affiché tel quel sur la page d'accueil.",
            ])
            ->add("dossierRequis", TextareaType::class, [
                "label" => "Dossiers requis",
                "attr" => ["class" => "form-control", "rows" => 5],
                "help" => "Une ligne = un document requis.",
            ])
            ->add("recompenses", TextareaType::class, [
                "label" => "Récompenses prévues",
                "attr" => ["class" => "form-control", "rows" => 5],
                "help" => "Une ligne = une récompense.",
            ])
            ->add("footerTexte", TextType::class, [
                "label" => "Texte du pied de page (Footer)",
                "attr" => ["class" => "form-control"],
                "required" => false,
            ])

            // ===== SOUUMISSION =====
            ->add("enregistrer", SubmitType::class, [
                "label" => "Enregistrer les modifications",
                "attr" => ["class" => "btn btn-success px-4 fw-bold"],
            ]);

        // Transformer pour convertir Array <=> String (Sauts de lignes) pour dossierRequis
        $builder
            ->get("dossierRequis")
            ->addModelTransformer(
                new CallbackTransformer(
                    fn(?array $array) => $array ? implode("\n", $array) : "",
                    fn(?string $string) => $string
                        ? array_filter(
                            array_map("trim", explode("\n", $string)),
                        )
                        : [],
                ),
            );

        // Transformer pour convertir Array <=> String (Sauts de lignes) pour recompenses
        $builder
            ->get("recompenses")
            ->addModelTransformer(
                new CallbackTransformer(
                    fn(?array $array) => $array ? implode("\n", $array) : "",
                    fn(?string $string) => $string
                        ? array_filter(
                            array_map("trim", explode("\n", $string)),
                        )
                        : [],
                ),
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => ParametrePage::class,
            // Permet d'insérer du HTML (comme l'icône Bootstrap) dans le label du bouton submit
            "label_format" => "%name%",
        ]);
    }
}
