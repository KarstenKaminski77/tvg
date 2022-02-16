<?php

namespace App\Controller\Admin;

use App\Entity\Clinics;
use App\Entity\User;
use App\Form\ClinicUsersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Message;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ClinicsCrudController extends AbstractCrudController
{
    private $mailer;
    private $plain_password;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public static function getEntityFqcn(): string
    {
        return Clinics::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            ->setPageTitle('index', 'Clinics')
            ->setPageTitle('new', 'New Clinic')
            ->setPageTitle('detail', fn (Clinics $clinics) => (string) $clinics)
            ->setPageTitle('edit', fn (Clinics $clinics) => sprintf('Editing <b>%s</b>', $clinics->getClinicName()))
            ->setEntityLabelInSingular('Clinic')
            ->setEntityLabelInPlural('Clinics');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', '#ID')->onlyOnIndex(),
            TextField::new('clinicName', 'Clinic Name')->setColumns(6),
            ImageField::new('logo', 'Logo')
                ->setBasePath('/images/logos')
                ->setUploadDir('/public/images/logos')
                ->setUploadedFileNamePattern('[contenthash]')
                ->setColumns(6),
            EmailField::new('email', 'Email')->setColumns(6),
            TextField::new('telephone', 'Telephone'),
            DateTimeField::new('modified', 'Modified')->onlyOnIndex(),
            DateTimeField::new('created', 'Created')->onlyOnIndex(),
            CollectionField::new('clinicUsers', 'User Accounts')
                ->setEntryType(ClinicUsersFormType::class)
                ->onlyOnForms()->setColumns(12)
        ];
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        $this->addEncodePasswordEventListener($formBuilder);

        return $formBuilder;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        $this->addEncodePasswordEventListener($formBuilder);

        return $formBuilder;
    }

    /**
     * @required
     */
    public function setEncoder(UserPasswordEncoderInterface $passwordEncoder): void
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function addEncodePasswordEventListener(FormBuilderInterface $formBuilder)
    {
        $formBuilder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var User $user */
            $user = $event->getData();
            $plain_password = $this->generatePassword();

            if (!empty($plain_password)) {

                $user->setPassword($this->passwordEncoder->encodePassword($user, $plain_password));

                // Send Email
                $body = '<table style="padding: 8px; border-collapse: collapse; border: none; font-family: arial">';
                $body .= '<tr><td colspan="2"><img src="https://huguenottunnel.nerdw.com/images/sanral-preferred-logo_cmyk.png"></td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr><td colspan="2">Please use the credentials below login to the SANRAL SMME portal.</td></tr>';
                $body .= '<tr><td colspan="2">&nbsp;</td></tr>';
                $body .= '<tr>';
                $body .= '    <td><b>URL: </b></td>';
                $body .= '    <td><a href="https://sanralesdd.co.za/admin">https://sanralesdd.co.za/admin</a></td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Username: </b></td>';
                $body .= '    <td>'. $user->getUsername() .'</td>';
                $body .= '</tr>';
                $body .= '<tr>';
                $body .= '    <td><b>Password: </b></td>';
                $body .= '    <td>'. $plain_password .'</td>';
                $body .= '</tr>';
                $body .= '</table>';

                $email = (new Email())
                    ->from('support@nerdw.com')
                    ->addTo($user->getUsername())
                    ->subject('SANRAL SMME Login Credentials')
                    ->html($body);

                $this->mailer->send($email);
            }
        });
    }

    private function generatePassword()
    {
        $sets = [];
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $sets[] = '23456789';
        $sets[] = '!@$%*?';

        $all = '';
        $password = '';

        foreach ($sets as $set) {

            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);

        for ($i = 0; $i < 16 - count($sets); $i++) {

            $password .= $all[array_rand($all)];
        }

        $this->plain_password = str_shuffle($password);

        return $this->plain_password;
    }
}
