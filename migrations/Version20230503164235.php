<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230503164235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('create table comment (id int auto_increment not null, author varchar(50) not null, comment text not null , parent_id int default null, rang int not null default 1, third_level_root int, created_at datetime not null default current_timestamp, updated_at datetime not null default current_timestamp, deleted_at datetime, index IDX_PARENT_ID (parent_id), index IDX_RANG (rang), index IDX_THIRD_LEVEL_ROOT (third_level_root), primary key(id)) default character set utf8mb4 collate `utf8mb4_unicode_ci` engine = InnoDB');
        $this->addSql('alter table comment add foreign key (parent_id) references comment(id)');
        $this->addSql('alter table comment add foreign key (third_level_root) references comment(id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE comment');
    }
}
