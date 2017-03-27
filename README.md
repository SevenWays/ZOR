                Zend On Rails by Sergej Hoffmann 0.9.1beta

This Module implements some functions of Ruby On Rails, such as the ActiveRecord Pattern and Migrations

                        ZendOnRails Instruction

Installation required programs.

  sudo apt-get install php7.0 php7.0-zip php7.0-intl php7.0-xml php7.0-sqlite
  sudo apt-get install composer
  sudo apt-get install git

Installation packages.

    Create Project Folder.
            mkdir <project_folder>
    Navigate to the project folder.
            cd <project_folder>
    Install the required packages with "Composer".

            composer require sevenways / zor: dev-master

Create

            alias ​​zor = 'vendor/sevenways/zor/bin/zor.php'
or use

        ./vendor/bin/zor.php


                      Working with ZendOnRails module

  zor.php create project [--path=]    Create an application. It uses  ZendApplicatioSkeleton
       
  [--path]    Optional if workspace differently

  zor.php create module --name= [--path=]    Create a module. It uses ZendModuleSkeleton

  [--name]    Name of Module
  [--path]    Optional if workspace differently

  zor.php create fmodule --require= [--path=]    Create a foreign module from packagist.org

  [--require]    Package name from packagist.org
  [--path]       Optional if workspace differently

  zor.php create database [--name=] [--driver=] [--username=] [--password=]    Create a database adapter. Default Sqlite

  [--name]        Name of tabel
  [--driver]      Zend Farmework supports drivers
  [--username]    Database username
  [--password]    Database password

  zor.php generate ctrl --name= [--module=] [--actions=]    Generate a controller

  [--name]       Name of controller
  [--module]     Name of module. Default: "Application"
  [--actions]    Names of actions. Default: "index"

  zor.php generate act [--cname=] [--module=] [--actions=]    Generate the actions for a controller

  [--cname]      Name of controller
  [--module]     Name of module. Default:"Application"
  [--actions]    Names of actions. Default: "index"

  zor.php generate model [--name=] [--module=] [--columns=]    Generate a model with ActiveRecord pattern

  [--name]       Name of model
  [--module]     Name of module. Default:"Application"
  [--columns]    A string of attributs.

  Structure of the string for the Columns: first_name:type{length}:primerykey/uniquekey,next_column:type{length}:Primerykey/uniquekey,...

  zor.php generate migration [--name=] [--columns=]    Generate a migration

  [--name]       Name of migration
  [--columns]    A string of attributs

  Structure of the string for the Columns: 

    first_name:type{length}:primerykey/uniquekey,next_column:type{length}:Primerykey/uniquekey,...


  zor.php run server [--host=] [--port=] [--path=]    Run buildin PHP server

  [--host]    Name of migration. Default: "localhost"
  [--port]    Port nummber. Default: "8080"
  [--path]    Path to index.php. Default: "/public"

  zor.php db migrate [--version]     Run migration to database
  zor.php db rollback [--version]    Run rollback to database

  [--version]    Version of migration. Default: any

               Working with ActiveRecord

Create database / model. The ZOR generated the model and migration with command:

  zor.php generate model [--name =] [--module=] [--columns=]

The attributes id, created_at, and updated_at are generated automatically. There are two ways to create the model:

First method: The generator binds every generated Model in Service Manager. This allows us to create the access from controller with the following call:

$obj = $this->serviceLocator->get('Namespace\Model\ModelName');

Because each ActiveRecord object implements an AdapterAwareInterface, Database Adapter is automatically added by the ServiceManager.

Second method: Via normal object generation, you have to transfer the database adapter to created object.

$obj = new ModelName();
$obj-> setDbAdapter($adapterObject);


                Add and modify records


The records can be added in two ways.

$obj->create(array (field_name1 => value, field_name2 => value, ...));
$obj->bind(array (field_name1 => value, field_name2 => value, ...));

Where bind() method is to execute save() method.

$obj->update_attributes(array (field_name1 => 'value', field_name2 => 'value', ...));
$obj->columnName = 'value';

For the individual changes of the values, one must execute the save() method.
$obj->save();

Useful methods:
$obj->isNewRecord();   // Verifies if Model is stored in the database.

$obj->isChanged();     // Returns whether a column value has been changed

            Working with individual entries

first($like=1) - returns the first entry from the database
last($like=1) - returns the last entry from the database
all() - returns all entries from the database
take($like) - is synonymous with first() where argument $like is required
find($id) - searches for one or more entries in the database by attributes(PrimaryKey). For multiple entries, you must pass ID as array().
find_by_attribute($name, $argument) - looks for an entry using the attribute name and value.

find_or_create_by_attribute ($column, $argument, $_) - this function checks if an entry exists, otherwise it inserts new.

With $_ variable you can pass an array with associated attributes and their value.

There are called magical methods:

find_by_* ($value);
find_or_create_by_* ($value, $_=null);

Instead of the asterisk, set the name of the attribute.

            Working with relationship 1:N

You have to make the settings in both models:

In the Model_1:
protected $ has_many = array('model_N' => array('class' => 'Namespace\ModelName');

In the Model_N:
protected $ belongs_to = array('model_1' => array ('class' => 'Namespace\ModelName', 'foreign_key_attribute' => 'model_name_id', 'foreign_key' => null);

$nModel = $ obj->NameOfNModel(); //gets new N-model

$obj->NameOfNModel()->create(array(field_name => value));
$all_n_mdele = $obj->NameOfNModel()->all(); // returns an array of N-relationship models.

            Working with relationship M:N

M:N relationships need one third Tabele, we can create one by means of generator the tabele.

Generate model Model_B[--module =] --columns=Model_M:references, Model_N:references

In the Model_B:

protected $ has_many = array('Model_M' => array ('class' => 'Namespace\ModelName_M'),
                             'Model_N' => array('class' => 'Namespace\ModelName_N'));

In the Model_M:

protected $ has_many = array ('Model_N' => array('class' => 'Namespace\ModelName','through' => 'Model_B');

In the Model_N:

protected $ belongs_to = array ('Model_M' => array ('class' => 'namespace\ModelName', 'foreign_key_attribute' => 'model_name_id', 'foreign_key' => null);
