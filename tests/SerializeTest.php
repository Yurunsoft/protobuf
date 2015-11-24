<?php

namespace ProtobufTest;

use Protobuf\Stream;
use Protobuf\Message;
use Protobuf\Collection;
use ProtobufTest\TestCase;
use ProtobufTest\Protos\Tree;
use ProtobufTest\Protos\Person;
use ProtobufTest\Protos\Simple;
use ProtobufTest\Protos\Repeated;
use ProtobufTest\Protos\Extension;
use ProtobufTest\Protos\AddressBook;
use ProtobufTest\Protos\Unrecognized;
use ProtobufTest\Protos\Person\PhoneType;
use ProtobufTest\Protos\Person\PhoneNumber;

class SerializeTest extends TestCase
{
    public function testWriteSimpleMessage()
    {
        $simple = new Simple();

        $simple->setBool(true);
        $simple->setBytes("bar");
        $simple->setString("foo");
        $simple->setFloat(12345.123);
        $simple->setUint32(123456789);
        $simple->setInt32(-123456789);
        $simple->setFixed32(123456789);
        $simple->setSint32(-123456789);
        $simple->setSfixed32(-123456789);
        $simple->setDouble(123456789.12345);
        $simple->setInt64(-123456789123456789);
        $simple->setUint64(123456789123456789);
        $simple->setFixed64(123456789123456789);
        $simple->setSint64(-123456789123456789);
        $simple->setSfixed64(-123456789123456789);

        $expected = $this->getProtoContent('simple.bin');
        $actual   = $simple->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $simple);
    }

    public function testWriteRepeatedString()
    {
        $repeated = new Repeated();

        $repeated->addString('one');
        $repeated->addString('two');
        $repeated->addString('three');

        $expected = $this->getProtoContent('repeated-string.bin');
        $actual   = $repeated->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $repeated);
    }

    public function testWriteRepeatedInt32()
    {
        $repeated = new Repeated();

        $repeated->addInt(1);
        $repeated->addInt(2);
        $repeated->addInt(3);

        $expected = $this->getProtoContent('repeated-int32.bin');
        $actual   = $repeated->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $repeated);
    }

    public function testWriteRepeatedNested()
    {
        $repeated = new Repeated();
        $nested1  = new Repeated\Nested();
        $nested2  = new Repeated\Nested();
        $nested3  = new Repeated\Nested();

        $nested1->setId(1);
        $nested2->setId(2);
        $nested3->setId(3);

        $repeated->addNested($nested1);
        $repeated->addNested($nested2);
        $repeated->addNested($nested3);

        $expected = $this->getProtoContent('repeated-nested.bin');
        $actual   = $repeated->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $repeated);
    }

    public function testWriteRepeatedPacked()
    {
        $repeated = new Repeated();

        $repeated->addPacked(1);
        $repeated->addPacked(2);
        $repeated->addPacked(3);

        $expected = $this->getProtoContent('repeated-packed.bin');
        $actual   = $repeated->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $repeated);
    }

    public function testWriteComplexMessage()
    {
        $phone1  = new PhoneNumber();
        $phone2  = new PhoneNumber();
        $phone3  = new PhoneNumber();
        $book    = new AddressBook();
        $person1 = new Person();
        $person2 = new Person();

        $person1->setId(2051);
        $person1->setName('John Doe');
        $person1->setEmail('john.doe@gmail.com');

        $person2->setId(23);
        $person2->setName('Iván Montes');
        $person2->setEmail('drslump@pollinimini.net');

        $book->addPerson($person1);
        $book->addPerson($person2);

        $person1->addPhone($phone1);
        $person1->addPhone($phone2);

        $phone1->setNumber('1231231212');
        $phone1->setType(PhoneType::HOME());

        $phone2->setNumber('55512321312');
        $phone2->setType(PhoneType::MOBILE());

        $phone3->setNumber('3493123123');
        $phone3->setType(PhoneType::WORK());

        $person2->addPhone($phone3);

        $expected = $this->getProtoContent('addressbook.bin');
        $actual   = $book->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $book);
    }

    public function testWriteTreeMessage()
    {
        $root  = new Tree\Node();
        $admin = new Tree\Node();
        $fabio = new Tree\Node();

        $root->setPath('/Users');
        $fabio->setPath('/Users/fabio');
        $admin->setPath('/Users/admin');

        // avoid recursion
        $parent = clone $root;

        $admin->setParent($parent);
        $fabio->setParent($parent);

        $root->addChildren($fabio);
        $root->addChildren($admin);

        $expected = $this->getProtoContent('tree.bin');
        $actual   = $root->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $root);
    }

    public function testWriteExtensionMessage()
    {
        $cat    = new Extension\Cat();
        $animal = new Extension\Animal();

        $cat->setDeclawed(true);

        $animal->setType(Extension\Animal\Type::CAT());
        $animal->extensions()->put(Extension\Cat::animal(), $cat);

        $expected = $this->getProtoContent('extension-cat.bin');
        $actual   = $animal->toStream();

        $this->assertEquals($expected, (string) $actual);
        $this->assertSerializedMessageSize($expected, $animal);
    }

    public function testReadSimpleMessage()
    {
        $binary = $this->getProtoContent('simple.bin');
        $simple = Simple::fromStream($binary);

        $this->assertInstanceOf(Simple::CLASS, $simple);
        $this->assertEquals('foo', $simple->getString());
        $this->assertEquals(-123456789, $simple->getInt32());
    }

    public function testReadRepeatedString()
    {
        $binary   = $this->getProtoContent('repeated-string.bin');
        $repeated = Repeated::fromStream($binary);

        $this->assertInstanceOf(Repeated::CLASS, $repeated);
        $this->assertInstanceOf(Collection::CLASS, $repeated->getStringList());
        $this->assertEquals(['one', 'two', 'three'], $repeated->getStringList()->getValues());
    }

    public function testReadRepeatedInt32()
    {
        $binary   = $this->getProtoContent('repeated-int32.bin');
        $repeated = Repeated::fromStream($binary);

        $this->assertInstanceOf(Repeated::CLASS, $repeated);
        $this->assertInstanceOf(Collection::CLASS, $repeated->getIntList());
        $this->assertEquals([1, 2, 3], $repeated->getIntList()->getValues());
    }

    public function testReadRepeatedNested()
    {
        $binary   = $this->getProtoContent('repeated-nested.bin');
        $repeated = Repeated::fromStream($binary);

        $this->assertInstanceOf(Repeated::CLASS, $repeated);
        $this->assertInstanceOf(Collection::CLASS, $repeated->getNestedList());
        $this->assertCount(3, $repeated->getNestedList());

        $this->assertInstanceOf(Repeated\Nested::CLASS, $repeated->getNestedList()[0]);
        $this->assertInstanceOf(Repeated\Nested::CLASS, $repeated->getNestedList()[1]);
        $this->assertInstanceOf(Repeated\Nested::CLASS, $repeated->getNestedList()[2]);

        $this->assertEquals(1, $repeated->getNestedList()[0]->getId());
        $this->assertEquals(2, $repeated->getNestedList()[1]->getId());
        $this->assertEquals(3, $repeated->getNestedList()[2]->getId());
    }

    public function testReadRepeatedPacked()
    {
        $binary   = $this->getProtoContent('repeated-packed.bin');
        $repeated = Repeated::fromStream($binary);

        $this->assertInstanceOf(Repeated::CLASS, $repeated);
        $this->assertInstanceOf(Collection::CLASS, $repeated->getPackedList());
        $this->assertEquals([1, 2, 3], $repeated->getPackedList()->getValues());
    }

    public function testReadComplexMessage()
    {
        $binary  = $this->getProtoContent('addressbook.bin');
        $complex = AddressBook::fromStream($binary);

        $this->assertInstanceOf(AddressBook::CLASS, $complex);
        $this->assertCount(2, $complex->getPersonList());

        $person1 = $complex->getPersonList()[0];
        $person2 = $complex->getPersonList()[1];

        $this->assertInstanceOf(Person::CLASS, $person1);
        $this->assertInstanceOf(Person::CLASS, $person2);

        $this->assertEquals($person1->getId(), 2051);
        $this->assertEquals($person1->getName(), 'John Doe');

        $this->assertEquals($person2->getId(), 23);
        $this->assertEquals($person2->getName(), 'Iván Montes');

        $this->assertCount(2, $person1->getPhoneList());
        $this->assertCount(1, $person2->getPhoneList());

        $this->assertEquals($person1->getPhoneList()[0]->getNumber(), '1231231212');
        $this->assertEquals($person1->getPhoneList()[0]->getType(), PhoneType::HOME());

        $this->assertEquals($person1->getPhoneList()[1]->getNumber(), '55512321312');
        $this->assertEquals($person1->getPhoneList()[1]->getType(), PhoneType::MOBILE());

        $this->assertEquals($person2->getPhoneList()[0]->getNumber(), '3493123123');
        $this->assertEquals($person2->getPhoneList()[0]->getType(), PhoneType::WORK());
    }

    public function testReadTreeMessage()
    {
        $binary = $this->getProtoContent('tree.bin');
        $root   = Tree\Node::fromStream($binary);

        $this->assertInstanceOf(Tree\Node::CLASS, $root);
        $this->assertCount(2, $root->getChildrenList());
        $this->assertEquals($root->getPath(), '/Users');

        $node1 = $root->getChildrenList()[0];
        $node2 = $root->getChildrenList()[1];

        $this->assertInstanceOf(Tree\Node::CLASS, $node1);
        $this->assertInstanceOf(Tree\Node::CLASS, $node2);

        $this->assertEquals('/Users/fabio', $node1->getPath());
        $this->assertEquals('/Users/admin', $node2->getPath());

        $this->assertInstanceOf(Tree\Node::CLASS, $node1->getParent());
        $this->assertInstanceOf(Tree\Node::CLASS, $node2->getParent());

        $this->assertEquals('/Users', $node1->getParent()->getPath());
        $this->assertEquals('/Users', $node2->getParent()->getPath());
    }

    public function testReadExtensionMessage()
    {
        $this->config->registerExtension(Extension\Cat::animal());

        $binary = $this->getProtoContent('extension-cat.bin');
        $animal = Extension\Animal::fromStream($binary, $this->config);

        $this->assertInstanceOf(Extension\Animal::CLASS, $animal);
        $this->assertInstanceOf(Collection::CLASS, $animal->extensions());
        $this->assertEquals(Extension\Animal\Type::CAT(), $animal->getType());

        $extensions = $animal->extensions();
        $cat        = $extensions->get(Extension\Cat::animal());

        $this->assertInstanceOf(Extension\Cat::CLASS, $cat);
        $this->assertTrue($cat->getDeclawed());
    }

    public function testUnknownFieldSet()
    {
        $binary       = $this->getProtoContent('unknown.bin');
        $unrecognized = Unrecognized::fromStream(Stream::create($binary));

        $this->assertInstanceOf(Unrecognized::CLASS, $unrecognized);
        $this->assertInstanceOf('Protobuf\UnknownFieldSet', $unrecognized->unknownFieldSet());
        $this->assertCount(15, $unrecognized->unknownFieldSet());

        $values = $unrecognized->unknownFieldSet();

        $this->assertInstanceOf('Protobuf\Unknown', $values->get(1));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(2));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(3));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(4));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(5));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(6));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(7));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(8));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(9));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(12));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(13));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(15));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(16));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(17));
        $this->assertInstanceOf('Protobuf\Unknown', $values->get(18));

        $this->assertEquals(4728057454355442093, $values->get(1)->value);
        $this->assertEquals(1178657918, $values->get(2)->value);
        $this->assertEquals(-123456789123456789, $values->get(3)->value);
        $this->assertEquals(123456789123456789, $values->get(4)->value);
        $this->assertEquals(-123456789, $values->get(5)->value);
        $this->assertEquals(123456789123456789, $values->get(6)->value);
        $this->assertEquals(123456789, $values->get(7)->value);
        $this->assertEquals(1, $values->get(8)->value);
        $this->assertEquals("foo", $values->get(9)->value);
        $this->assertEquals("bar", $values->get(12)->value);
        $this->assertEquals(123456789, $values->get(13)->value);
        $this->assertEquals(4171510507, $values->get(15)->value);
        $this->assertEquals(-123456789123456789, $values->get(16)->value);
        $this->assertEquals(246913577, $values->get(17)->value);
        $this->assertEquals(246913578246913577, $values->get(18)->value);
    }

    public function assertSerializedMessageSize($expectedContent, $message)
    {
        $context      = $this->config->createComputeSizeContext();
        $expectedSize = mb_strlen($expectedContent, '8bit');
        $actualSize   = $message->serializedSize($context);

        $this->assertEquals($expectedSize, $actualSize);
    }
}
