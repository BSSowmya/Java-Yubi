package org.example;

import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class UnitTestTest {
    static int a =0;
    static int b =0;
    @BeforeAll
    public static void setBeforeClass(){
    System.out.println("****** Assigning the values ************");
    a=10;
    b=12;
    }


    @Test
    public  void test(){
        UnitTest unitTest = new UnitTest();
        int result =  unitTest.addNumbers(a, b);
        assertEquals(22, result);

    }

}