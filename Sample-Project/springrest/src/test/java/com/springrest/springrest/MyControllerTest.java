package com.springrest.springrest;

import com.springrest.springrest.controller.MyController;
import org.junit.Before;
import org.junit.Test;
import org.junit.runner.RunWith;
import org.springframework.boot.test.autoconfigure.web.servlet.WebMvcTest;
import org.springframework.test.context.junit4.SpringRunner;
import org.springframework.test.web.servlet.MockMvc;
import org.springframework.test.web.servlet.MockMvcBuilder;
import org.springframework.test.web.servlet.RequestBuilder;
import org.springframework.test.web.servlet.setup.MockMvcBuilders;
import org.springframework.web.context.WebApplicationContext;

import static javax.swing.UIManager.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@RunWith(SpringRunner.class)
@WebMvcTest(MyController.class)
public class MyControllerTest extends  SpringrestApplicationTests{


    private WebApplicationContext webApplicationContext;
    private MockMvc mvc;
    @Before
    public void setUp(){
    mvc = MockMvcBuilders.webAppContextSetup(webApplicationContext).build();

    }

    @Test
    public void test() throws Exception {
        mvc.perform((RequestBuilder) get("/employees"))
                .andExpect(content().contentType("application/json;charset=UTF-8"))
                .andExpect(jsonPath("$.name").value("emp1")).andExpect(jsonPath("$.designation").value("manager"))
                .andExpect(jsonPath("$.empId").value("1")).andExpect(jsonPath("$.salary").value(3000));


    }
}
